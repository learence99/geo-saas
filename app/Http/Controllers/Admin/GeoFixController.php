<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoFix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeoFixController extends Controller
{
    private const PYTHON_BIN = '/opt/geo-optimizer-venv/bin/python3';

    private const WRAPPER_SCRIPT = '/opt/geo-optimizer-venv/geo_fix_json.py';

    private const CATEGORIES = [
        'robots' => 'Robots.txt',
        'llms' => 'llms.txt',
        'schema' => 'JSON-LD 结构化数据',
        'meta' => 'Meta 标签',
        'ai_discovery' => 'AI Discovery',
        'content' => '内容质量',
    ];

    public function index(): View
    {
        $fixes = GeoFix::orderByDesc('created_at')->paginate(15);
        $latest = GeoFix::where('status', 'completed')->orderByDesc('created_at')->first();
        $defaultUrl = rtrim((string) config('app.url'), '/').'/';

        return view('admin.geo-fixes.index', [
            'pageTitle' => 'GEO 修复建议',
            'activeMenu' => 'geo_fix',
            'fixes' => $fixes,
            'latest' => $latest,
            'defaultUrl' => $defaultUrl,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function show(int $id): View
    {
        $fix = GeoFix::findOrFail($id);

        return view('admin.geo-fixes.show', [
            'pageTitle' => 'GEO 修复详情',
            'activeMenu' => 'geo_fix',
            'fix' => $fix,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'only' => ['nullable', 'array'],
            'only.*' => ['string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
        ]);

        $onlyCategories = $validated['only'] ?? [];

        $fix = GeoFix::create([
            'url' => $validated['url'],
            'only_categories' => $onlyCategories ? implode(',', $onlyCategories) : null,
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $args = [self::PYTHON_BIN, self::WRAPPER_SCRIPT, '--url', $validated['url']];
            if ($onlyCategories) {
                $args[] = '--only';
                $args[] = implode(',', $onlyCategories);
            }

            $process = new Process($args);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            if (!empty($result['error'])) {
                throw new \RuntimeException((string) $result['error']);
            }

            $fix->update([
                'score_before' => $result['score_before'] ?? null,
                'score_estimated_after' => $result['score_estimated_after'] ?? null,
                'fixes' => $result['fixes'] ?? [],
                'skipped' => $result['skipped'] ?? [],
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.geo-fixes.show', $fix->id)
                ->with('message', '修复方案已生成');
        } catch (\Throwable $e) {
            $fix->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.geo-fixes.index')
                ->withErrors(['url' => '生成失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        GeoFix::findOrFail($id)->delete();

        return redirect()->route('admin.geo-fixes.index')->with('message', '记录已删除');
    }
}
