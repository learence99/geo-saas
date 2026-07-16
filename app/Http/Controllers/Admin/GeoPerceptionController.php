<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoPerception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeoPerceptionController extends Controller
{
    private const PYTHON_BIN = '/opt/geo-optimizer-venv/bin/geo';

    public function index(): View
    {
        $perceptions = GeoPerception::orderByDesc('created_at')->paginate(15);
        $latest = GeoPerception::where('status', 'completed')->orderByDesc('created_at')->first();
        $defaultUrl = rtrim((string) config('app.url'), '/').'/';

        return view('admin.geo-perceptions.index', [
            'pageTitle' => 'AI 认知快照',
            'activeMenu' => 'geo_perception',
            'perceptions' => $perceptions,
            'latest' => $latest,
            'defaultUrl' => $defaultUrl,
        ]);
    }

    public function show(int $id): View
    {
        $perception = GeoPerception::findOrFail($id);

        return view('admin.geo-perceptions.show', [
            'pageTitle' => 'AI 认知快照详情',
            'activeMenu' => 'geo_perception',
            'perception' => $perception,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $perception = GeoPerception::create([
            'url' => $validated['url'],
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $process = new Process([
                self::PYTHON_BIN,
                'perception',
                '--url', $validated['url'],
                '--format', 'json',
            ]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            $perception->update([
                'mode' => $result['mode'] ?? null,
                'brand_name' => $result['brand_name'] ?? null,
                'brand_entity_type' => $result['brand_entity_type'] ?? null,
                'main_topic' => $result['main_topic'] ?? null,
                'detected_audience' => $result['detected_audience'] ?? null,
                'citability_grade' => $result['citability_grade'] ?? null,
                'trust_score' => isset($result['trust_score']) ? (int) round((float) $result['trust_score']) : null,
                'ai_readable_summary' => $result['ai_readable_summary'] ?? null,
                'detected_services' => $result['detected_services'] ?? [],
                'evidence_snippets' => $result['evidence_snippets'] ?? [],
                'supported_claims' => $result['supported_claims'] ?? [],
                'unsupported_claims' => $result['unsupported_claims'] ?? [],
                'citation_worthy_facts' => $result['citation_worthy_facts'] ?? [],
                'ambiguities' => $result['ambiguities'] ?? [],
                'missing_authority_signals' => $result['missing_authority_signals'] ?? [],
                'schema_types_present' => $result['schema_types_present'] ?? [],
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.geo-perceptions.show', $perception->id)
                ->with('message', '快照已生成');
        } catch (\Throwable $e) {
            $perception->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.geo-perceptions.index')
                ->withErrors(['url' => '生成失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        GeoPerception::findOrFail($id)->delete();

        return redirect()->route('admin.geo-perceptions.index')->with('message', '记录已删除');
    }
}
