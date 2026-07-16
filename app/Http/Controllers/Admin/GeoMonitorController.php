<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeoMonitorController extends Controller
{
    private const PYTHON_BIN = '/opt/geo-optimizer-venv/bin/geo';

    public function index(): View
    {
        $monitors = GeoMonitor::orderByDesc('created_at')->paginate(15);
        $latest = GeoMonitor::where('status', 'completed')->orderByDesc('created_at')->first();
        $defaultDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        return view('admin.geo-monitors.index', [
            'pageTitle' => 'AI 可见度监控',
            'activeMenu' => 'geo_tools',
            'monitors' => $monitors,
            'latest' => $latest,
            'defaultDomain' => $defaultDomain,
        ]);
    }

    public function show(int $id): View
    {
        $monitor = GeoMonitor::findOrFail($id);

        return view('admin.geo-monitors.show', [
            'pageTitle' => 'AI 可见度监控详情',
            'activeMenu' => 'geo_tools',
            'monitor' => $monitor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $monitor = GeoMonitor::create([
            'domain' => $validated['domain'],
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $process = new Process([
                self::PYTHON_BIN,
                'monitor',
                '--domain', $validated['domain'],
                '--format', 'json',
                '--save-history',
            ]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            $monitor->update([
                'url' => $result['url'] ?? null,
                'mode' => $result['mode'] ?? null,
                'visibility_score' => $result['visibility_score'] ?? null,
                'band' => $result['band'] ?? null,
                'total_snapshots' => $result['total_snapshots'] ?? null,
                'score_delta' => $result['score_delta'] ?? null,
                'latest_geo_score' => $result['latest_geo_score'] ?? null,
                'latest_geo_band' => $result['latest_geo_band'] ?? null,
                'signals' => $result['signals'] ?? [],
                'recommendations' => $result['recommendations'] ?? [],
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.geo-monitors.show', $monitor->id)
                ->with('message', '监控快照已生成');
        } catch (\Throwable $e) {
            $monitor->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.geo-monitors.index')
                ->withErrors(['domain' => '生成失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        GeoMonitor::findOrFail($id)->delete();

        return redirect()->route('admin.geo-monitors.index')->with('message', '记录已删除');
    }
}
