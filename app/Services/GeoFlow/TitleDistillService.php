<?php

namespace App\Services\GeoFlow;

use App\Models\AiModel;
use App\Models\Keyword;
use App\Support\GeoFlow\ApiKeyCrypto;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Throwable;

use function Laravel\Ai\agent;

/**
 * 标题蒸馏服务:把一个关键词蒸馏成一个可发布的母标题 + 判定页面类型。
 * 默认模型(无需界面选)。
 */
class TitleDistillService
{
    public const PAGE_TYPES = ['行程决策页', '季节决策页', '路线决策页', '对比转化页', '费用说明页', '报名转化页', '风险说明页'];

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function defaultModel(): ?AiModel
    {
        $id = 0;
        try {
            $id = (int) \App\Models\SiteSetting::query()->where('setting_key', 'geo_default_model_id')->value('setting_value');
        } catch (Throwable) {
            $id = 0;
        }
        $query = AiModel::query()->where('status', 'active')->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'");
        if ($id > 0 && ($m = (clone $query)->whereKey($id)->first())) {
            return $m;
        }

        return $query->orderBy('id')->first();
    }

    /**
     * @return array{ok:bool,title?:string,page_type?:string,error?:string,fallback_used?:bool}
     */
    public function generate(Keyword $kw, string $subject): array
    {
        $model = $this->defaultModel();
        if (! $model) {
            return ['ok' => false, 'error' => '未配置可用的生成模型'];
        }
        try {
            $content = $this->requestFromModel($model, $kw, $subject);
            $parsed = $this->parse($content, $kw);
            if ($parsed['title'] !== '') {
                return ['ok' => true, 'title' => $parsed['title'], 'page_type' => $parsed['page_type']];
            }
        } catch (Throwable $e) {
            return ['ok' => true, 'title' => $this->mockTitle($kw), 'page_type' => $this->inferType($kw), 'fallback_used' => true];
        }

        return ['ok' => true, 'title' => $this->mockTitle($kw), 'page_type' => $this->inferType($kw), 'fallback_used' => true];
    }

    private function requestFromModel(AiModel $model, Keyword $kw, string $subject): string
    {
        $providerUrl = OpenAiRuntimeProvider::resolveChatBaseUrl((string) ($model->api_url ?? ''));
        if ($providerUrl === '') {
            throw new \RuntimeException('ai_url_missing');
        }
        $apiKey = $this->apiKeyCrypto->decrypt((string) ($model->getRawOriginal('api_key') ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('ai_key_missing');
        }
        $driver = OpenAiRuntimeProvider::resolveChatDriver($providerUrl, (string) ($model->model_id ?? ''));
        $providerName = OpenAiRuntimeProvider::registerProvider('title_distill', $driver, $providerUrl, $apiKey);

        if (trim($subject) === '') {
            $subject = (string) \App\Support\AdminWeb::siteName();
        }
        $pack = (string) ($kw->pack ?? '');
        $packName = (string) (config("geo_packs.{$pack}.name") ?: '通用');
        $forbidden = config("geo_packs.{$pack}.lexicon.forbidden", ['保证', '包过', '最低价', '零风险']);
        $forbiddenText = implode('、', array_slice((array) $forbidden, 0, 10));
        $types = implode('|', self::PAGE_TYPES);

        $system = "你是「{$subject}」的资深 GEO 标题策略师,服务{$packName}行业。任务:把一个用户关键词蒸馏成一个可发布、AI 乐于引用的母标题,并判定页面类型。";
        $user = "关键词:「{$kw->keyword}」(意图:" . ($kw->intent ?: '未知') . ",主题:" . ($kw->category ?: '未知') . ")。\n"
            . "生成 1 个母标题:在关键词基础上扩成完整、口语化、含具体决策变量的标题(可加一个副问钩子,如「…？…差多少」);服务「{$subject}」获客但去营销腔,禁止出现 {$forbiddenText} 等词。\n"
            . "并判定 page_type,只能取其一:{$types}。\n"
            . "只输出一个 JSON 对象:{\"title\":\"...\",\"page_type\":\"...\"},不要解释、不要代码块。";

        $response = agent($system)->prompt($user, [], $providerName, (string) ($model->model_id ?? ''));
        $raw = (string) ($response->text ?? '');
        $content = OpenAiRuntimeProvider::normalizeGeneratedText($raw);
        if ($content === '') {
            throw new \RuntimeException('ai_empty_content');
        }

        return $content;
    }

    /**
     * @return array{title:string,page_type:string}
     */
    private function parse(string $content, Keyword $kw): array
    {
        $content = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/u', '', trim($content)));
        $s = strpos($content, '{');
        $e = strrpos($content, '}');
        if ($s !== false && $e !== false && $e > $s) {
            $content = substr($content, $s, $e - $s + 1);
        }
        $data = json_decode($content, true);
        $title = is_array($data) ? trim((string) ($data['title'] ?? '')) : '';
        $type = is_array($data) ? trim((string) ($data['page_type'] ?? '')) : '';
        if ($title === '') {
            // 退一步:整段当标题(去引号)
            $title = trim($content, "\"'{}");
            if (mb_strlen($title) > 60 || str_contains($title, '"')) {
                $title = '';
            }
        }

        return [
            'title' => mb_substr($title, 0, 120),
            'page_type' => in_array($type, self::PAGE_TYPES, true) ? $type : $this->inferType($kw),
        ];
    }

    private function inferType(Keyword $kw): string
    {
        $t = (string) ($kw->category . $kw->keyword);
        return match (true) {
            str_contains($t, '费用') || str_contains($t, '自费') || str_contains($t, '价格') => '费用说明页',
            str_contains($t, '退') || str_contains($t, '取消') || str_contains($t, '风险') || str_contains($t, '购物') => '风险说明页',
            str_contains($t, '报名') || str_contains($t, '付款') || str_contains($t, '余位') => '报名转化页',
            str_contains($t, '自由行') || str_contains($t, '跟团') || str_contains($t, '对比') => '对比转化页',
            str_contains($t, '季节') || str_contains($t, '几月') => '季节决策页',
            str_contains($t, '路线') || str_contains($t, '经典') => '路线决策页',
            default => '行程决策页',
        };
    }

    private function mockTitle(Keyword $kw): string
    {
        return rtrim((string) $kw->keyword, '？?') . '？第一次去应该怎么选';
    }
}
