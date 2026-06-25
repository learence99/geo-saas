<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\KeywordLibrary;
use App\Models\Title;
use App\Models\TitleLibrary;
use App\Services\GeoEngine\Engine;
use App\Services\GeoEngine\GeoScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 放置：项目根 /app/Http/Controllers/GeoEngineController.php
 * 纯新增。引擎页 + 闭环入库 + GEO 内容评分。
 */
class GeoEngineController extends Controller
{
    public function index()
    {
        return view('geoengine.generate', ['packs' => Engine::packs()]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:50',
            'pack' => 'required|string',
            'subject' => 'nullable|string|max:50',
            'count' => 'required|integer|min:1|max:10',
        ]);

        try {
            $result = Engine::generate($data['keyword'], (int) $data['count'], $data['pack'], $data['subject'] ?? '');

            return response()->json(['ok' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** 闭环：把引擎产出一键写入 GEOFlow 的 标题库 / 关键词库 */
    public function saveToLibrary(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:50',
            'target' => 'required|in:title,keyword',
            'name' => 'nullable|string|max:80',
            'items' => 'required|array|min:1',
            'items.*.text' => 'required|string',
            'items.*.merchantVersion' => 'required|string',
        ]);

        $name = trim((string) ($data['name'] ?? '')) ?: ($data['keyword'] . ' · 引擎生成');
        $desc = '由 GEO 引擎从核心词「' . $data['keyword'] . '」生成';
        $items = $data['items'];

        try {
            if ($data['target'] === 'title') {
                [$libId, $n] = DB::transaction(function () use ($name, $desc, $items) {
                    $lib = TitleLibrary::query()->create([
                        'name' => $name, 'description' => $desc, 'title_count' => 0,
                        'generation_type' => 'ai', 'generation_rounds' => 1, 'is_ai_generated' => 1,
                    ]);
                    $n = 0; $seen = [];
                    foreach ($items as $it) {
                        $title = trim((string) $it['merchantVersion']);
                        if ($title === '' || isset($seen[$title])) { continue; }
                        $seen[$title] = true;
                        Title::query()->create([
                            'library_id' => $lib->id, 'title' => $title,
                            'keyword' => trim((string) $it['text']), 'is_ai_generated' => true,
                            'used_count' => 0, 'usage_count' => 0,
                        ]);
                        $n++;
                    }
                    $lib->update(['title_count' => $n]);
                    return [$lib->id, $n];
                });

                return response()->json([
                    'ok' => true, 'target' => 'title', 'library_id' => $libId, 'count' => $n,
                    'url' => route('admin.title-libraries.index'),
                    'message' => "已写入标题库「{$name}」，共 {$n} 条标题",
                ]);
            }

            [$libId, $n] = DB::transaction(function () use ($name, $desc, $items) {
                $lib = KeywordLibrary::query()->create([
                    'name' => $name, 'description' => $desc, 'keyword_count' => 0,
                ]);
                $n = 0; $seen = [];
                foreach ($items as $it) {
                    $kw = trim((string) $it['text']);
                    if ($kw === '' || isset($seen[$kw])) { continue; }
                    $seen[$kw] = true;
                    Keyword::query()->create([
                        'library_id' => $lib->id, 'keyword' => $kw, 'used_count' => 0, 'usage_count' => 0,
                    ]);
                    $n++;
                }
                $lib->update(['keyword_count' => $n]);
                return [$lib->id, $n];
            });

            return response()->json([
                'ok' => true, 'target' => 'keyword', 'library_id' => $libId, 'count' => $n,
                'url' => route('admin.keyword-libraries.index'),
                'message' => "已写入关键词库「{$name}」，共 {$n} 条",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** GEO 内容评分页 */
    public function scorePage()
    {
        return view('geoengine.score');
    }

    public function score(Request $request)
    {
        $data = $request->validate([
            'content' => 'required|string|max:50000',
            'keyword' => 'nullable|string|max:60',
        ]);

        return response()->json(['ok' => true] + GeoScorer::score($data['content'], $data['keyword'] ?? ''));
    }
}
