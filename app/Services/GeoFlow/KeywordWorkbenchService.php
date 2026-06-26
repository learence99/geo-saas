<?php

namespace App\Services\GeoFlow;

use App\Models\AiModel;
use App\Support\GeoFlow\ApiKeyCrypto;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Throwable;

use function Laravel\Ai\agent;

/**
 * 关键词工作台 · 结构化生成服务。
 * 核心词 + 行业包 → 一批关键词,每个带 主题/意图/阶段/价值 标签(demo 商业分类法)。
 * 模型走"默认生成模型"(无需界面选)。
 */
class KeywordWorkbenchService
{
    public const INTENTS = ['信息型', '决策型', '交易型', '风险型', '品牌型'];
    public const STAGES = ['知晓期', '决策期', '转化期'];
    public const VALUES = ['低', '中', '中高', '高', '很高'];

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    /**
     * 解析"默认生成模型":SiteSetting geo_default_model_id,否则第一个 active chat 模型。
     */
    public function defaultModel(): ?AiModel
    {
        $id = 0;
        try {
            $row = \App\Models\SiteSetting::query()->where('setting_key', 'geo_default_model_id')->value('setting_value');
            $id = (int) $row;
        } catch (Throwable) {
            $id = 0;
        }

        $query = AiModel::query()
            ->where('status', 'active')
            ->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'");

        if ($id > 0) {
            $m = (clone $query)->whereKey($id)->first();
            if ($m) {
                return $m;
            }
        }

        return $query->orderBy('id')->first();
    }

    /**
     * @return array{ok:bool,items?:list<array{keyword:string,category:string,intent:string,stage:string,value:string}>,error?:string,fallback_used?:bool}
     */
    public function generate(string $core, int $count, string $pack, string $subject, array $bias = []): array
    {
        $model = $this->defaultModel();
        if (! $model) {
            return ['ok' => false, 'error' => '未配置可用的生成模型,请先在 AI 配置器添加一个 active 聊天模型'];
        }

        try {
            $content = $this->requestFromModel($model, $core, $count, $pack, $subject, $bias);
            $items = $this->parse($content, $core);
            if ($items !== []) {
                return ['ok' => true, 'items' => $items];
            }
        } catch (Throwable $e) {
            return ['ok' => true, 'items' => $this->mock($core, $count), 'fallback_used' => true];
        }

        return ['ok' => true, 'items' => $this->mock($core, $count), 'fallback_used' => true];
    }

    private function requestFromModel(AiModel $model, string $core, int $count, string $pack, string $subject, array $bias): string
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
        $providerName = OpenAiRuntimeProvider::registerProvider('keyword_workbench', $driver, $providerUrl, $apiKey);

        if (trim($subject) === '') {
            $subject = (string) \App\Support\AdminWeb::siteName();
        }
        $packName = (string) (config("geo_packs.{$pack}.name") ?: '通用');
        $forbidden = config("geo_packs.{$pack}.lexicon.forbidden", ['保证', '包过', '最低价', '零风险']);
        $forbiddenText = implode('、', array_slice((array) $forbidden, 0, 10));

        $biasParts = [];
        if (! empty($bias['intent'])) { $biasParts[] = '意图偏向「' . $bias['intent'] . '」'; }
        if (! empty($bias['stage'])) { $biasParts[] = '阶段偏向「' . $bias['stage'] . '」'; }
        if (! empty($bias['value'])) { $biasParts[] = '价值偏向「' . $bias['value'] . '」'; }
        $biasText = $biasParts ? ('；本批' . implode('、', $biasParts)) : '；覆盖完整决策旅程(知晓→决策→转化)';

        $system = "你是「{$subject}」的资深 GEO 需求挖掘专家,服务{$packName}行业。任务:围绕核心词挖掘用户在 AI/搜索里真实会搜、会问、会咨询的关键词,并给每个词打结构标签。";

        $user = "围绕核心词「{$core}」生成 {$count} 个关键词{$biasText}。\n"
            . "每个关键词输出一个 JSON 对象,字段固定:\n"
            . "- keyword: 关键词(口语化、贴近真实搜索/提问)\n"
            . "- category: 主题(2-6字,如 退改风险、费用说明、方式对比、行程规划)\n"
            . "- intent: 只能取 信息型|决策型|交易型|风险型|品牌型\n"
            . "- stage: 只能取 知晓期|决策期|转化期\n"
            . "- value: 商业价值,只能取 低|中|中高|高|很高(越接近报名/付款/退改/咨询越高)\n"
            . "要求:覆盖决策旅程;去营销腔,禁止出现 {$forbiddenText} 等词;品牌相关词归 品牌型。\n"
            . "只输出 JSON 数组,形如 [{\"keyword\":\"...\",\"category\":\"...\",\"intent\":\"...\",\"stage\":\"...\",\"value\":\"...\"}],不要任何解释或代码块标记。";

        $response = agent($system)->prompt($user, [], $providerName, (string) ($model->model_id ?? ''));
        $raw = (string) ($response->text ?? '');
        $content = OpenAiRuntimeProvider::normalizeGeneratedText($raw);
        if ($content === '') {
            throw new \RuntimeException('ai_empty_content');
        }

        return $content;
    }

    /**
     * @return list<array{keyword:string,category:string,intent:string,stage:string,value:string}>
     */
    private function parse(string $content, string $core): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $content);
        $start = strpos($content, '[');
        $end = strrpos($content, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }
            $kw = trim((string) ($row['keyword'] ?? ''));
            if ($kw === '' || isset($seen[$kw]) || mb_strlen($kw, 'UTF-8') > 40) {
                continue;
            }
            $seen[$kw] = true;
            $out[] = [
                'keyword' => $kw,
                'category' => mb_substr(trim((string) ($row['category'] ?? '未分类')), 0, 12),
                'intent' => $this->pick((string) ($row['intent'] ?? ''), self::INTENTS, '信息型'),
                'stage' => $this->pick((string) ($row['stage'] ?? ''), self::STAGES, '知晓期'),
                'value' => $this->pick((string) ($row['value'] ?? ''), self::VALUES, '中'),
            ];
        }

        return $out;
    }

    private function pick(string $v, array $allowed, string $default): string
    {
        $v = trim($v);
        return in_array($v, $allowed, true) ? $v : $default;
    }

    /**
     * @return list<array{keyword:string,category:string,intent:string,stage:string,value:string}>
     */
    private function mock(string $core, int $count): array
    {
        $tpl = [
            ['费用包含什么', '费用说明', '交易型', '转化期', '高'],
            ['取消能退吗', '退改风险', '风险型', '转化期', '很高'],
            ['几天合适', '行程规划', '信息型', '知晓期', '中'],
            ['自由行还是跟团', '方式对比', '决策型', '决策期', '高'],
            ['怎么报名付款', '报名付款', '交易型', '转化期', '很高'],
            ['适合带父母吗', '父母游', '决策型', '决策期', '高'],
        ];
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $t = $tpl[$i % count($tpl)];
            $out[] = ['keyword' => $core . $t[0], 'category' => $t[1], 'intent' => $t[2], 'stage' => $t[3], 'value' => $t[4]];
        }

        return $out;
    }
}
