<?php

namespace App\Services\GeoFlow;

use App\Models\AiModel;
use App\Support\GeoFlow\ApiKeyCrypto;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Throwable;

use function Laravel\Ai\agent;

/**
 * 关键词 AI 扩词服务（GEO SaaS 新增，补原生空白）。
 * 核心词 → 行业包 keyword_expansion prompt → 关键词列表。
 * 与 TitleAiGenerationService 同构：复用 ai_models / OpenAiRuntimeProvider / Laravel\Ai。
 */
class KeywordAiGenerationService
{
    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    /**
     * @return array{keywords:list<string>,fallback_used:bool,fallback_reason:?string}
     */
    public function generate(AiModel $aiModel, string $core, int $count, string $pack = '', string $subject = ''): array
    {
        try {
            $content = $this->requestFromModel($aiModel, $core, $count, $pack, $subject);
            $keywords = $this->parse($content);
            if ($keywords !== []) {
                return ['keywords' => $keywords, 'fallback_used' => false, 'fallback_reason' => null];
            }
        } catch (Throwable $e) {
            return ['keywords' => $this->mock($core, $count), 'fallback_used' => true, 'fallback_reason' => $e->getMessage()];
        }

        return ['keywords' => $this->mock($core, $count), 'fallback_used' => true, 'fallback_reason' => 'empty_result'];
    }

    private function requestFromModel(AiModel $aiModel, string $core, int $count, string $pack, string $subject): string
    {
        $providerUrl = OpenAiRuntimeProvider::resolveChatBaseUrl((string) ($aiModel->api_url ?? ''));
        if ($providerUrl === '') {
            throw new \RuntimeException('ai_url_missing');
        }

        $apiKey = $this->apiKeyCrypto->decrypt((string) ($aiModel->getRawOriginal('api_key') ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('ai_key_missing');
        }

        $driver = OpenAiRuntimeProvider::resolveChatDriver($providerUrl, (string) ($aiModel->model_id ?? ''));
        $providerName = OpenAiRuntimeProvider::registerProvider('keyword_ai', $driver, $providerUrl, $apiKey);

        if (trim($subject) === '') {
            $subject = (string) \App\Support\AdminWeb::siteName();
        }

        $packPrompt = $pack !== '' ? config("geo_packs.{$pack}.prompts.keyword_expansion") : null;

        if (is_array($packPrompt) && ! empty($packPrompt['system']) && ! empty($packPrompt['user_template'])) {
            $vars = [
                '{{subject}}' => $subject,
                '{{core}}' => $core,
                '{{count}}' => (string) $count,
            ];
            $systemPrompt = strtr((string) $packPrompt['system'], $vars);
            $userPrompt = strtr((string) $packPrompt['user_template'], $vars);
        } else {
            $systemPrompt = '你是关键词扩展专家。请根据核心词生成口语化、贴近真实搜索的关键词。';
            $userPrompt = "请围绕核心词「{$core}」生成 {$count} 个关键词，每个独占一行，不要序号或符号。";
        }

        try {
            $response = agent($systemPrompt)->prompt($userPrompt, [], $providerName, (string) ($aiModel->model_id ?? ''));
        } catch (Throwable $e) {
            throw new \RuntimeException(OpenAiRuntimeProvider::normalizeApiException($e, $providerUrl), 0, $e);
        }

        $raw = (string) ($response->text ?? '');
        $content = OpenAiRuntimeProvider::normalizeGeneratedText($raw);
        if ($content === '') {
            throw new \RuntimeException('ai_empty_content');
        }

        return $content;
    }

    /**
     * @return list<string>
     */
    private function parse(string $content): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $kw = preg_replace('/^\d+[\.\)\-、\s]*/u', '', trim($line));
            $kw = trim((string) $kw);
            $kw = trim($kw, "\"'「」【】[]（）()· \t");
            if ($kw === '' || mb_strlen($kw, 'UTF-8') > 40) {
                continue;
            }
            $out[] = $kw;
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    private function mock(string $core, int $count): array
    {
        $suffix = ['攻略', '费用大概多少', '适合几天', '适合人群', '怎么报名', '自由行还是跟团', '班期与余位', '需要注意什么'];
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $core . $suffix[$i % count($suffix)];
        }

        return array_values(array_unique($out));
    }
}
