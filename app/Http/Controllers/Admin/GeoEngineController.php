<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\KeywordLibrary;
use App\Models\Title;
use App\Models\TitleLibrary;
use App\Services\GeoFlow\Engine;
use App\Support\AdminWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 选词引擎 = 素材库的「AI 智能生成」入口。
 * 核心词 → 问题集群 / GEO 标题，再"新建库"或"追加到已有库"，喂进素材库。
 * 原生后台页：/geo_admin/geo-engine（admin.geo-engine.*），受 admin.auth 保护。
 */
class GeoEngineController extends Controller
{
    public function index()
    {
        return view('admin.geo-engine.index', [
            'pageTitle' => '选词引擎',
            'activeMenu' => 'geo_engine',
            'adminSiteName' => AdminWeb::siteName(),
            'packs' => Engine::packs(),
            'titleLibraries' => TitleLibrary::query()->orderByDesc('id')->get(['id', 'name']),
            'keywordLibraries' => KeywordLibrary::query()->orderByDesc('id')->get(['id', 'name']),
        ]);
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

    /**
     * 入库：新建库或追加到已有库（标题库 / 关键词库）。
     */
    public function saveToLibrary(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:50',
            'target' => 'required|in:title,keyword',
            'mode' => 'required|in:new,append',
            'name' => 'nullable|string|max:80',
            'library_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.text' => 'required|string',
            'items.*.merchantVersion' => 'required|string',
        ]);

        if ($data['mode'] === 'append' && empty($data['library_id'])) {
            return response()->json(['ok' => false, 'error' => '请选择要追加的库'], 422);
        }

        $isTitle = $data['target'] === 'title';
        $items = $data['items'];
        $newName = trim((string) ($data['name'] ?? '')) ?: ($data['keyword'] . ' · 引擎生成');
        $desc = '由 GEO 引擎从核心词「' . $data['keyword'] . '」生成';

        try {
            $result = DB::transaction(function () use ($data, $isTitle, $items, $newName, $desc) {
                if ($isTitle) {
                    $lib = $data['mode'] === 'append'
                        ? TitleLibrary::query()->findOrFail($data['library_id'])
                        : TitleLibrary::query()->create([
                            'name' => $newName, 'description' => $desc, 'title_count' => 0,
                            'generation_type' => 'ai', 'generation_rounds' => 1, 'is_ai_generated' => 1,
                        ]);
                    $seen = array_flip(Title::query()->where('library_id', $lib->id)->pluck('title')->all());
                    $n = 0;
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
                    $lib->update(['title_count' => Title::query()->where('library_id', $lib->id)->count()]);

                    return ['name' => $lib->name, 'n' => $n, 'url' => route('admin.title-libraries.index'), 'label' => '标题库'];
                }

                $lib = $data['mode'] === 'append'
                    ? KeywordLibrary::query()->findOrFail($data['library_id'])
                    : KeywordLibrary::query()->create([
                        'name' => $newName, 'description' => $desc, 'keyword_count' => 0,
                    ]);
                $seen = array_flip(Keyword::query()->where('library_id', $lib->id)->pluck('keyword')->all());
                $n = 0;
                foreach ($items as $it) {
                    $kw = trim((string) $it['text']);
                    if ($kw === '' || isset($seen[$kw])) { continue; }
                    $seen[$kw] = true;
                    Keyword::query()->create([
                        'library_id' => $lib->id, 'keyword' => $kw, 'used_count' => 0, 'usage_count' => 0,
                    ]);
                    $n++;
                }
                $lib->update(['keyword_count' => Keyword::query()->where('library_id', $lib->id)->count()]);

                return ['name' => $lib->name, 'n' => $n, 'url' => route('admin.keyword-libraries.index'), 'label' => '关键词库'];
            });

            $verb = $data['mode'] === 'append' ? '追加到' : '写入';

            return response()->json([
                'ok' => true, 'count' => $result['n'], 'url' => $result['url'],
                'message' => "已{$verb}{$result['label']}「{$result['name']}」，新增 {$result['n']} 条",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
