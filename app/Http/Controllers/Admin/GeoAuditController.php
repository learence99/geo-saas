<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeoAuditController extends Controller
{
    private const PYTHON_BIN = '/opt/geo-optimizer-venv/bin/geo';

    public function index(): View
    {
        $audits = GeoAudit::orderByDesc('created_at')->paginate(15);
        $latest = GeoAudit::where('status', 'completed')->orderByDesc('created_at')->first();
        $defaultUrl = rtrim((string) config('app.url'), '/').'/';

        return view('admin.geo-audits.index', [
            'pageTitle' => 'GEO 审计',
            'activeMenu' => 'geo_audit',
            'audits' => $audits,
            'latest' => $latest,
            'defaultUrl' => $defaultUrl,
        ]);
    }

    public function show(int $id): View
    {
        $audit = GeoAudit::findOrFail($id);

        return view('admin.geo-audits.show', [
            'pageTitle' => 'GEO 审计详情',
            'activeMenu' => 'geo_audit',
            'audit' => $audit,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $audit = GeoAudit::create([
            'url' => $validated['url'],
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $process = new Process([
                self::PYTHON_BIN,
                'audit',
                '--url', $validated['url'],
                '--format', 'json',
            ]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            $audit->update([
                'score' => $result['score'] ?? null,
                'band' => $result['band'] ?? null,
                'checks' => $result['checks'] ?? null,
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.geo-audits.show', $audit->id)
                ->with('message', "审计完成，得分 {$audit->score}/100");
        } catch (\Throwable $e) {
            $audit->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.geo-audits.index')
                ->withErrors(['url' => '审计失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        GeoAudit::findOrFail($id)->delete();

        return redirect()->route('admin.geo-audits.index')->with('message', '记录已删除');
    }
}
