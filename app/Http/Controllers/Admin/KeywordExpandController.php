<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Keyword;
use App\Models\KeywordLibrary;
use App\Services\GeoFlow\KeywordAiGenerationService;
use App\Support\AdminWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 关键词库 · AI 扩词（GEO SaaS 新增，原生空白）。
 * 入口在原生关键词库详情页的「AI 扩词」按钮；写入当前库的 keywords 表。
 * 路由经 GeoSaasServiceProvider 注入：admin.keyword-libraries.ai-expand(.submit)
 */
class KeywordExpandController extends Controller
{
    public function __construct(private readonly KeywordAiGenerationService $service) {}

    public function form(int $libraryId): View
    {
        $library = KeywordLibrary::query()->whereKey($libraryId)->firstOrFail();
        $aiModels = AiModel::query()
            ->select(['id', 'name', 'model_id'])
            ->where('status', 'active')
            ->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'")
            ->orderBy('name')
            ->get();

        return view('admin.keyword-libraries.ai-expand', [
            'pageTitle' => 'AI 扩词',
            'activeMenu' => 'materials',
            'adminSiteName' => AdminWeb::siteName(),
            'library' => $library,
            'aiModels' => $aiModels,
            'packs' => config('geo_packs', []),
        ]);
    }

    /**
     * 创建页「用 AI 生成并创建」：建库 + 生成 + 写入，一步完成。
     */
    public function createWithAi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'core' => ['required', 'string', 'max:50'],
            'pack' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:60'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'ai_model_id' => ['required', 'integer'],
        ]);

        $aiModel = AiModel::query()
            ->whereKey((int) $data['ai_model_id'])
            ->where('status', 'active')
            ->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'")
            ->firstOrFail();

        $library = KeywordLibrary::query()->create([
            'name' => trim((string) $data['name']),
            'description' => '由 AI 扩词从核心词「' . $data['core'] . '」生成',
            'keyword_count' => 0,
        ]);

        $result = $this->service->generate(
            $aiModel,
            trim((string) $data['core']),
            (int) $data['count'],
            (string) ($data['pack'] ?? ''),
            trim((string) ($data['subject'] ?? ''))
        );

        $saved = 0;
        foreach ($result['keywords'] as $kw) {
            $kw = trim((string) $kw);
            if ($kw === '') {
                continue;
            }
            Keyword::query()->firstOrCreate(
                ['library_id' => $library->id, 'keyword' => $kw],
                ['used_count' => 0, 'usage_count' => 0]
            );
            $saved++;
        }
        $library->update(['keyword_count' => Keyword::query()->where('library_id', $library->id)->count()]);

        $msg = "已创建关键词库「{$library->name}」并 AI 生成 {$saved} 个关键词";
        if (($result['fallback_used'] ?? false) === true) {
            $msg .= '（AI 服务不可用，已用模板兜底）';
        }

        return redirect()
            ->route('admin.keyword-libraries.detail', ['libraryId' => $library->id])
            ->with('message', $msg);
    }

    public function submit(Request $request, int $libraryId): RedirectResponse
    {
        $library = KeywordLibrary::query()->whereKey($libraryId)->firstOrFail();

        $data = $request->validate([
            'core' => ['required', 'string', 'max:50'],
            'pack' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:60'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'ai_model_id' => ['required', 'integer'],
        ]);

        $aiModel = AiModel::query()
            ->whereKey((int) $data['ai_model_id'])
            ->where('status', 'active')
            ->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'")
            ->firstOrFail();

        $result = $this->service->generate(
            $aiModel,
            trim((string) $data['core']),
            (int) $data['count'],
            (string) ($data['pack'] ?? ''),
            trim((string) ($data['subject'] ?? ''))
        );

        $seen = array_flip(Keyword::query()->where('library_id', $libraryId)->pluck('keyword')->all());
        $saved = 0;
        $dup = 0;
        foreach ($result['keywords'] as $kw) {
            $kw = trim((string) $kw);
            if ($kw === '') {
                continue;
            }
            if (isset($seen[$kw])) {
                $dup++;

                continue;
            }
            $seen[$kw] = true;
            Keyword::query()->create([
                'library_id' => $libraryId,
                'keyword' => $kw,
                'used_count' => 0,
                'usage_count' => 0,
            ]);
            $saved++;
        }
        $library->update(['keyword_count' => Keyword::query()->where('library_id', $libraryId)->count()]);

        $msg = "AI 扩词完成，新增 {$saved} 个关键词";
        if ($dup > 0) {
            $msg .= "，跳过重复 {$dup} 个";
        }
        if (($result['fallback_used'] ?? false) === true) {
            $msg .= '（AI 服务不可用，已用模板兜底）';
        }

        return redirect()
            ->route('admin.keyword-libraries.detail', ['libraryId' => $libraryId])
            ->with('message', $msg);
    }
}
