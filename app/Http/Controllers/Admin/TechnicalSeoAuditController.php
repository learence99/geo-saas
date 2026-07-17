<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicalSeoAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TechnicalSeoAuditController extends Controller
{
    private const LIGHTHOUSE_BIN = '/usr/lib/node_modules/lighthouse/cli/index.js';

    private const NODE_BIN = '/usr/bin/node';

    private const CATEGORIES = ['performance', 'seo', 'accessibility', 'best-practices'];

    private const CORE_WEB_VITALS_AUDITS = [
        'largest-contentful-paint',
        'cumulative-layout-shift',
        'total-blocking-time',
        'first-contentful-paint',
        'speed-index',
    ];

    public function index(): View
    {
        $audits = TechnicalSeoAudit::orderByDesc('created_at')->paginate(15);
        $latest = TechnicalSeoAudit::where('status', 'completed')->orderByDesc('created_at')->first();
        $defaultUrl = rtrim((string) config('app.url'), '/').'/';

        return view('admin.technical-seo-audits.index', [
            'pageTitle' => '技术SEO审计',
            'activeMenu' => 'geo_tools',
            'audits' => $audits,
            'latest' => $latest,
            'defaultUrl' => $defaultUrl,
        ]);
    }

    public function show(int $id): View
    {
        $audit = TechnicalSeoAudit::findOrFail($id);

        return view('admin.technical-seo-audits.show', [
            'pageTitle' => '技术SEO审计详情',
            'activeMenu' => 'geo_tools',
            'audit' => $audit,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $audit = TechnicalSeoAudit::create([
            'url' => $validated['url'],
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $reportPath = storage_path('app/lighthouse-reports/'.uniqid('lh_', true).'.json');
            if (!is_dir(dirname($reportPath))) {
                mkdir(dirname($reportPath), 0755, true);
            }

            $process = new Process([
                self::NODE_BIN,
                self::LIGHTHOUSE_BIN,
                $validated['url'],
                '--output=json',
                '--output-path='.$reportPath,
                '--chrome-flags=--headless=new --no-sandbox --disable-gpu',
                '--only-categories='.implode(',', self::CATEGORIES),
                '--quiet',
            ]);
            $process->setTimeout(150);
            $process->run();

            if (!is_file($reportPath)) {
                throw new ProcessFailedException($process);
            }

            $report = json_decode((string) file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);
            @unlink($reportPath);

            $parsed = $this->parseReport($report);

            $audit->update([
                'performance_score' => $parsed['scores']['performance'],
                'seo_score' => $parsed['scores']['seo'],
                'accessibility_score' => $parsed['scores']['accessibility'],
                'best_practices_score' => $parsed['scores']['best-practices'],
                'core_web_vitals' => $parsed['coreWebVitals'],
                'issues' => $parsed['issues'],
                'lighthouse_version' => (string) ($report['lighthouseVersion'] ?? ''),
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.technical-seo-audits.show', $audit->id)
                ->with('message', '技术SEO审计完成');
        } catch (\Throwable $e) {
            $audit->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.technical-seo-audits.index')
                ->withErrors(['url' => '审计失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        TechnicalSeoAudit::findOrFail($id)->delete();

        return redirect()->route('admin.technical-seo-audits.index')->with('message', '记录已删除');
    }

    /**
     * @param  array<string,mixed>  $report
     * @return array{scores: array<string,int|null>, coreWebVitals: array<int, array<string,mixed>>, issues: array<string, array<int, array<string,string>>>}
     */
    private function parseReport(array $report): array
    {
        $categories = is_array($report['categories'] ?? null) ? $report['categories'] : [];
        $audits = is_array($report['audits'] ?? null) ? $report['audits'] : [];

        $scores = [];
        foreach (self::CATEGORIES as $categoryKey) {
            $score = $categories[$categoryKey]['score'] ?? null;
            $scores[$categoryKey] = is_numeric($score) ? (int) round(((float) $score) * 100) : null;
        }

        $coreWebVitals = [];
        foreach (self::CORE_WEB_VITALS_AUDITS as $auditId) {
            $a = is_array($audits[$auditId] ?? null) ? $audits[$auditId] : [];
            if ($a === []) {
                continue;
            }
            $coreWebVitals[] = [
                'id' => $auditId,
                'title' => (string) ($a['title'] ?? $auditId),
                'display_value' => (string) ($a['displayValue'] ?? ''),
                'score' => isset($a['score']) && is_numeric($a['score']) ? (float) $a['score'] : null,
            ];
        }

        $issues = [];
        foreach (self::CATEGORIES as $categoryKey) {
            $refs = is_array($categories[$categoryKey]['auditRefs'] ?? null) ? $categories[$categoryKey]['auditRefs'] : [];
            $categoryIssues = [];
            foreach ($refs as $ref) {
                $auditId = (string) ($ref['id'] ?? '');
                $a = is_array($audits[$auditId] ?? null) ? $audits[$auditId] : [];
                if ($a === [] || !array_key_exists('score', $a) || $a['score'] === null) {
                    continue;
                }
                if ((float) $a['score'] >= 0.9 || ($a['scoreDisplayMode'] ?? '') === 'notApplicable') {
                    continue;
                }
                $categoryIssues[] = [
                    'title' => (string) ($a['title'] ?? $auditId),
                    'display_value' => (string) ($a['displayValue'] ?? ''),
                    'description' => mb_substr((string) ($a['description'] ?? ''), 0, 300),
                ];
            }
            if ($categoryIssues !== []) {
                $issues[$categoryKey] = $categoryIssues;
            }
        }

        return [
            'scores' => $scores,
            'coreWebVitals' => $coreWebVitals,
            'issues' => $issues,
        ];
    }
}
