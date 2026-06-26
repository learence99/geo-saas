<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use App\Models\KeywordLibrary;
use App\Models\Title;
use App\Models\TitleLibrary;
use App\Services\GeoFlow\KeywordWorkbenchService;
use App\Services\GeoFlow\TitleDistillService;
use App\Support\AdminWeb;
use Illuminate\Http\Request;

/**
 * 关键词库管理(自有功能模块)。
 * 跑在原生 keywords 表(加法扩列)上,菜单指这里。下游产物仍走原生标题/任务。
 * 路由经 GeoSaasServiceProvider 注入:admin.keyword-workbench.*
 */
class KeywordWorkbenchController extends Controller
{
    public function __construct(
        private readonly KeywordWorkbenchService $service,
        private readonly TitleDistillService $distill,
    ) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $intent = (string) $request->query('intent', '');
        $value = (string) $request->query('value', '');
        $status = (string) $request->query('status', '');

        $q = Keyword::query()->orderByDesc('id');
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('keyword', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('core_word', 'like', "%{$search}%");
            });
        }
        if ($intent !== '') { $q->where('intent', $intent); }
        if ($value !== '') { $q->where('value', $value); }
        if ($status !== '') { $q->where('status', $status); }

        $keywords = $q->paginate(30)->withQueryString();

        return view('admin.keyword-workbench.index', [
            'pageTitle' => '关键词库管理',
            'activeMenu' => 'keyword_workbench',
            'adminSiteName' => AdminWeb::siteName(),
            'keywords' => $keywords,
            'packs' => config('geo_packs', []),
            'intents' => KeywordWorkbenchService::INTENTS,
            'stages' => KeywordWorkbenchService::STAGES,
            'values' => KeywordWorkbenchService::VALUES,
            'filters' => compact('search', 'intent', 'value', 'status'),
            'defaultModel' => optional($this->service->defaultModel())->name,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'core' => ['required', 'string', 'max:50'],
            'pack' => ['required', 'string'],
            'subject' => ['nullable', 'string', 'max:60'],
            'count' => ['required', 'integer', 'min:1', 'max:30'],
            'intent' => ['nullable', 'string'],
            'stage' => ['nullable', 'string'],
            'value' => ['nullable', 'string'],
        ]);

        $result = $this->service->generate(
            trim($data['core']),
            (int) $data['count'],
            (string) $data['pack'],
            trim((string) ($data['subject'] ?? '')),
            ['intent' => $data['intent'] ?? '', 'stage' => $data['stage'] ?? '', 'value' => $data['value'] ?? '']
        );

        if (! ($result['ok'] ?? false)) {
            return back()->withErrors($result['error'] ?? '生成失败');
        }

        $core = trim($data['core']);
        $packName = (string) (config("geo_packs.{$data['pack']}.name") ?: '通用');
        $lib = KeywordLibrary::query()->firstOrCreate(
            ['name' => $core . ' · ' . $packName],
            ['description' => 'AI 扩词来源核心词:' . $core, 'keyword_count' => 0]
        );

        $seen = array_flip(Keyword::query()->where('library_id', $lib->id)->pluck('keyword')->all());
        $saved = 0;
        foreach ($result['items'] as $it) {
            $kw = trim((string) $it['keyword']);
            if ($kw === '' || isset($seen[$kw])) { continue; }
            $seen[$kw] = true;
            (new Keyword())->forceFill([
                'library_id' => $lib->id,
                'keyword' => $kw,
                'used_count' => 0,
                'usage_count' => 0,
                'core_word' => $core,
                'pack' => (string) $data['pack'],
                'intent' => (string) $it['intent'],
                'stage' => (string) $it['stage'],
                'category' => (string) $it['category'],
                'value' => (string) $it['value'],
                'source' => 'ai',
                'status' => '待处理',
            ])->save();
            $saved++;
        }
        $lib->update(['keyword_count' => Keyword::query()->where('library_id', $lib->id)->count()]);

        $msg = "已围绕「{$core}」生成 {$saved} 个结构化关键词";
        if (($result['fallback_used'] ?? false) === true) {
            $msg .= '（AI 服务不可用,已用模板兜底）';
        }

        return redirect()->route('admin.keyword-workbench.index')->with('message', $msg);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:60'],
            'core_word' => ['nullable', 'string', 'max:50'],
            'pack' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:20'],
            'intent' => ['required', 'string'],
            'stage' => ['required', 'string'],
            'value' => ['required', 'string'],
        ]);

        $core = trim((string) ($data['core_word'] ?? '')) ?: '手动录入';
        $packName = (string) (config("geo_packs.{$data['pack']}.name") ?: '通用');
        $lib = KeywordLibrary::query()->firstOrCreate(
            ['name' => $core . ' · ' . $packName],
            ['description' => '手动录入', 'keyword_count' => 0]
        );

        (new Keyword())->forceFill([
            'library_id' => $lib->id,
            'keyword' => trim($data['keyword']),
            'used_count' => 0,
            'usage_count' => 0,
            'core_word' => $core,
            'pack' => (string) ($data['pack'] ?? ''),
            'intent' => (string) $data['intent'],
            'stage' => (string) $data['stage'],
            'category' => (string) ($data['category'] ?? '未分类'),
            'value' => (string) $data['value'],
            'source' => 'manual',
            'status' => '待处理',
        ])->save();
        $lib->update(['keyword_count' => Keyword::query()->where('library_id', $lib->id)->count()]);

        return redirect()->route('admin.keyword-workbench.index')->with('message', '关键词已保存');
    }

    /**
     * 蒸馏:关键词 → 母标题(入对应核心词的标题库,带血缘 + 回写关键词状态)。
     */
    public function distillTitle(Request $request)
    {
        $data = $request->validate(['id' => ['required', 'integer']]);
        $kw = Keyword::query()->find($data['id']);
        if (! $kw) {
            return response()->json(['ok' => false, 'error' => '关键词不存在'], 404);
        }

        $result = $this->distill->generate($kw, (string) AdminWeb::siteName());
        if (! ($result['ok'] ?? false)) {
            return response()->json(['ok' => false, 'error' => $result['error'] ?? '生成失败'], 422);
        }

        // 标题库与关键词库一一对应(绑 keyword_library_id)
        $kwLib = KeywordLibrary::query()->find($kw->library_id);
        $libName = ($kw->core_word !== null && $kw->core_word !== '') ? ($kw->core_word . ' · 标题') : (($kwLib->name ?? '母标题库') . ' · 标题');
        $titleLib = TitleLibrary::query()->firstOrCreate(
            ['keyword_library_id' => $kw->library_id],
            ['name' => $libName, 'description' => '蒸馏自关键词库:' . ($kwLib->name ?? ''), 'title_count' => 0, 'generation_type' => 'ai', 'is_ai_generated' => 1]
        );

        // 同一关键词已蒸馏过则不重复
        $exists = Title::query()->where('library_id', $titleLib->id)->where('keyword', $kw->keyword)->exists();
        if (! $exists) {
            $priority = in_array((string) $kw->value, ['高', '很高', '中高'], true) ? 'P1' : 'P2';
            (new Title())->forceFill([
                'library_id' => $titleLib->id,
                'title' => $result['title'],
                'keyword' => $kw->keyword,
                'is_ai_generated' => true,
                'used_count' => 0,
                'usage_count' => 0,
                'page_type' => $result['page_type'],
                'value' => $kw->value,
                'priority' => $priority,
                'status' => '未生成',
                'source' => 'ai',
                'pack' => $kw->pack,
                'core_word' => $kw->core_word,
            ])->save();
            $titleLib->update(['title_count' => Title::query()->where('library_id', $titleLib->id)->count()]);
        }

        $kw->forceFill(['status' => '已生成标题'])->save();

        return response()->json([
            'ok' => true,
            'title' => $result['title'],
            'page_type' => $result['page_type'],
            'fallback_used' => $result['fallback_used'] ?? false,
        ]);
    }
}
