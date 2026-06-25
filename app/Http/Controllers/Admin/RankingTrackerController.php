<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackedPrompt;
use App\Models\VisibilitySnapshot;
use App\Services\GeoFlow\VisibilityCollector;
use App\Support\AdminWeb;
use Illuminate\Http\Request;

/**
 * AI 排名/可见度追踪。原生后台页：/geo_admin/ranking-tracker（admin.ranking-tracker.*）。
 */
class RankingTrackerController extends Controller
{
    public function index()
    {
        $prompts = TrackedPrompt::query()->with('latestSnapshot')->orderByDesc('id')->get();

        $rows = [];
        $cited = 0;
        $rankSum = 0;
        $rankN = 0;
        foreach ($prompts as $p) {
            $snap = $p->latestSnapshot;
            if ($snap) {
                if ($snap->is_cited) { $cited++; }
                if ($snap->rank !== null) { $rankSum += (int) $snap->rank; $rankN++; }
            }
            $rows[] = [
                'id' => $p->id, 'subject' => $p->subject, 'prompt' => $p->prompt, 'engine' => $p->engine,
                'snap' => $snap ? [
                    'is_cited' => (bool) $snap->is_cited,
                    'rank' => $snap->rank,
                    'competitors' => $snap->competitors ?: [],
                    'sentiment' => $snap->sentiment,
                    'checked_at' => optional($snap->checked_at)->format('Y-m-d H:i'),
                ] : null,
            ];
        }

        return view('admin.ranking-tracker.index', [
            'pageTitle' => 'AI 排名追踪',
            'activeMenu' => 'ranking_tracker',
            'adminSiteName' => AdminWeb::siteName(),
            'rows' => $rows,
            'engines' => VisibilityCollector::engines(),
            'metrics' => [
                'tracked' => count($rows),
                'cited' => $cited,
                'avg_rank' => $rankN > 0 ? round($rankSum / $rankN, 1) : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:100',
            'prompt' => 'required|string|max:500',
            'engine' => 'required|string|max:32',
        ]);
        TrackedPrompt::query()->create($data);

        return response()->json(['ok' => true]);
    }

    public function check(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer']);
        $p = TrackedPrompt::query()->find($data['id']);
        if (!$p) {
            return response()->json(['ok' => false, 'error' => '记录不存在'], 404);
        }

        try {
            $r = VisibilityCollector::check($p->prompt, $p->subject, $p->engine);
            VisibilitySnapshot::query()->create([
                'tracked_prompt_id' => $p->id,
                'engine' => $r['engine'],
                'is_cited' => $r['is_cited'],
                'rank' => $r['rank'],
                'competitors' => $r['competitors'],
                'sentiment' => $r['sentiment'],
                'raw_answer' => mb_substr($r['answer'], 0, 4000),
                'checked_at' => now(),
            ]);

            return response()->json([
                'ok' => true,
                'is_cited' => $r['is_cited'], 'rank' => $r['rank'],
                'competitors' => $r['competitors'], 'sentiment' => $r['sentiment'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
