<?php

namespace App\Http\Controllers;

use App\Services\GeoEngine\VisibilityCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 放置：项目根 /app/Http/Controllers/RankingTrackerController.php
 * AI 排名/可见度追踪（MVP）。纯新增。
 */
class RankingTrackerController extends Controller
{
    public function index()
    {
        $prompts = DB::table('tracked_prompts')->orderByDesc('id')->get();

        $rows = [];
        $citedCount = 0;
        $rankSum = 0;
        $rankN = 0;
        foreach ($prompts as $p) {
            $snap = DB::table('visibility_snapshots')
                ->where('tracked_prompt_id', $p->id)
                ->orderByDesc('id')->first();
            if ($snap) {
                if ($snap->is_cited) { $citedCount++; }
                if ($snap->rank !== null) { $rankSum += (int) $snap->rank; $rankN++; }
            }
            $rows[] = [
                'id' => $p->id, 'subject' => $p->subject, 'prompt' => $p->prompt, 'engine' => $p->engine,
                'snap' => $snap ? [
                    'is_cited' => (bool) $snap->is_cited,
                    'rank' => $snap->rank,
                    'competitors' => $snap->competitors ? (json_decode($snap->competitors, true) ?: []) : [],
                    'sentiment' => $snap->sentiment,
                    'checked_at' => $snap->checked_at,
                ] : null,
            ];
        }

        return view('geoengine.rankings', [
            'rows' => $rows,
            'engines' => VisibilityCollector::engines(),
            'metrics' => [
                'tracked' => count($rows),
                'cited' => $citedCount,
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
        DB::table('tracked_prompts')->insert([
            'subject' => $data['subject'], 'prompt' => $data['prompt'], 'engine' => $data['engine'],
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function check(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer']);
        $p = DB::table('tracked_prompts')->where('id', $data['id'])->first();
        if (!$p) {
            return response()->json(['ok' => false, 'error' => '记录不存在'], 404);
        }

        try {
            $r = VisibilityCollector::check($p->prompt, $p->subject, $p->engine);
            DB::table('visibility_snapshots')->insert([
                'tracked_prompt_id' => $p->id,
                'engine' => $r['engine'],
                'is_cited' => $r['is_cited'],
                'rank' => $r['rank'],
                'competitors' => json_encode($r['competitors'], JSON_UNESCAPED_UNICODE),
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
