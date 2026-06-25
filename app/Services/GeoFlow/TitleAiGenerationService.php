<?php

namespace App\Services\GeoFlow;

use App\Models\AiModel;
use App\Support\GeoFlow\ApiKeyCrypto;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Throwable;

use function Laravel\Ai\agent;

/**
 * 标题 AI 生成服务。
 *
 * 【GEO SaaS 二开覆盖】prompt 从写死改为「行业包驱动」：
 *   - 按 pack 读 config('geo_packs.{pack}.prompts.title_generation')；
 *   - pack / subject 优先取方法入参，其次从 request() 兜底（无需改控制器）；
 *   - 未配置行业包时，完全回退到原生 prompt，行为不变。
 * 覆盖原文件：app/Services/GeoFlow/TitleAiGenerationService.php
 */
class TitleAiGenerationService
{
    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    /**
     * 生成标题列表。
     *
     * @param  list<string>  $keywords
     * @return array{titles:list<string>,fallback_used:bool,fallback_reason:?string}
     */
    public function generateTitles(
        AiModel $aiModel,
        array $keywords,
        int $count,
        string $style,
        string $customPrompt = '',
        string $pack = '',
        string $subject = ''
    ): array {
        try {
            $content = $this->requestTitlesFromModel($aiModel, $keywords, $count, $style, $customPrompt, $pack, $subject);
            $titles = $this->parseGeneratedTitles($content);
            if ($titles !== []) {
                return [
                    'titles' => $titles,
                    'fallback_used' => false,
                    'fallback_reason' => null,
                ];
            }
        } catch (Throwable $exception) {
            return [
                'titles' => $this->generateMockTitles($keywords, $count, $style),
                'fallback_used' => true,
                'fallback_reason' => $exception->getMessage(),
            ];
        }

        return [
            'titles' => $this->generateMockTitles($keywords, $count, $style),
            'fallback_used' => true,
            'fallback_reason' => 'empty_result',
        ];
    }

    /**
     * 请求真实模型生成标题。
     *
     * @param  list<string>  $keywords
     */
    private function requestTitlesFromModel(
        AiModel $aiModel,
        array $keywords,
        int $count,
        string $style,
        string $customPrompt,
        string $pack = '',
        string $subject = ''
    ): string {
        $providerUrl = OpenAiRuntimeProvider::resolveChatBaseUrl((string) ($aiModel->api_url ?? ''));
        if ($providerUrl === '') {
            throw new \RuntimeException('ai_url_missing');
        }

        $apiKey = $this->decryptApiKey((string) ($aiModel->getRawOriginal('api_key') ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('ai_key_missing');
        }

        $driver = OpenAiRuntimeProvider::resolveChatDriver($providerUrl, (string) ($aiModel->model_id ?? ''));
        $providerName = OpenAiRuntimeProvider::registerProvider('title_ai', $driver, $providerUrl, $apiKey);

        $styleMap = [
            'professional' => '专业严谨的',
            'attractive' => '吸引眼球的',
            'seo' => 'SEO优化的',
            'creative' => '创意新颖的',
            'question' => '疑问式的',
        ];
        $styleDescription = $styleMap[$style] ?? '专业严谨的';
        $keywordsText = implode('、', $keywords);

        // ── GEO SaaS：行业包驱动的 prompt ──────────────────────────────
        // 行业 / 主体：优先方法入参，其次从 request 兜底（避免改原生控制器）
        $pack = $pack !== '' ? $pack : (string) request()->input('pack', '');
        $subject = $subject !== '' ? $subject : (string) request()->input('subject', '');
        if (trim($subject) === '') {
            $subject = (string) \App\Support\AdminWeb::siteName();
        }

        $packPrompt = $pack !== '' ? config("geo_packs.{$pack}.prompts.title_generation") : null;

        if (is_array($packPrompt) && ! empty($packPrompt['system']) && ! empty($packPrompt['user_template'])) {
            $vars = [
                '{{subject}}' => $subject,
                '{{keywords}}' => $keywordsText,
                '{{count}}' => (string) $count,
                '{{style}}' => $styleDescription,
                '{{custom}}' => $customPrompt !== '' ? ('额外要求：' . $customPrompt) : '',
            ];
            $systemPrompt = strtr((string) $packPrompt['system'], $vars);
            $userPrompt = strtr((string) $packPrompt['user_template'], $vars);
        } else {
            // 原生兜底：未配置行业包时与原版完全一致
            $systemPrompt = "你是一个专业的内容标题生成专家。请根据提供的关键词生成{$styleDescription}文章标题。";
            $userPrompt = "请基于以下关键词生成 {$count} 个{$styleDescription}文章标题：\n\n关键词：{$keywordsText}\n\n";
            if ($customPrompt !== '') {
                $userPrompt .= "额外要求：{$customPrompt}\n\n";
            }
            $userPrompt .= "要求：\n1. 每个标题独占一行\n2. 标题要有吸引力和可读性\n3. 适合搜索引擎优化\n4. 不要添加序号或其他标记\n5. 直接输出标题内容";
        }
        // ───────────────────────────────────────────────────────────

        try {
            $response = agent($systemPrompt)->prompt(
                $userPrompt,
                [],
                $providerName,
                (string) ($aiModel->model_id ?? '')
            );
        } catch (Throwable $exception) {
            throw new \RuntimeException(OpenAiRuntimeProvider::normalizeApiException($exception, $providerUrl), 0, $exception);
        }

        $rawContent = (string) ($response->text ?? '');
        $content = OpenAiRuntimeProvider::normalizeGeneratedText($rawContent);

        if ($content === '') {
            if (OpenAiRuntimeProvider::looksLikeSseCompletionPayload($rawContent)) {
                throw new \RuntimeException('ai_empty_stream_content');
            }

            throw new \RuntimeException('ai_empty_content');
        }

        return $content;
    }

    /**
     * 解析模型输出文本为标题列表。
     *
     * @return list<string>
     */
    private function parseGeneratedTitles(string $content): array
    {
        $titles = [];
        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $title = preg_replace('/^\d+[\.\)\-、\s]*/u', '', trim($line));
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $titles[] = $title;
        }

        return array_values(array_unique($titles));
    }

    /**
     * 解密 ai_models 中的 API Key（兼容旧系统 enc:v1 格式）。
     */
    private function decryptApiKey(string $storedApiKey): string
    {
        return $this->apiKeyCrypto->decrypt($storedApiKey);
    }

    /**
     * @return list<string>
     */
    private function generateMockTitles(array $keywords, int $count, string $style): array
    {
        $styleTemplates = [
            'professional' => [
                '{keyword}的深度分析与研究',
                '关于{keyword}的专业见解',
                '{keyword}行业发展趋势报告',
            ],
            'attractive' => [
                '你绝对不知道的{keyword}秘密',
                '揭秘{keyword}背后的故事',
                '{keyword}让人意想不到的用途',
            ],
            'seo' => [
                '{keyword}完整指南：从入门到精通',
                '{keyword}常见问题解答大全',
                '如何选择最适合的{keyword}方案',
            ],
            'creative' => [
                '重新定义{keyword}的可能性',
                '如果{keyword}会说话，它会告诉你什么？',
                '当{keyword}遇上创新思维',
            ],
            'question' => [
                '{keyword}真的有用吗？',
                '为什么{keyword}如此重要？',
                '{keyword}的未来在哪里？',
            ],
        ];

        $templates = $styleTemplates[$style] ?? $styleTemplates['professional'];
        $titles = [];
        for ($index = 0; $index < $count; $index++) {
            $keyword = $keywords[array_rand($keywords)];
            $template = $templates[array_rand($templates)];
            $titles[] = str_replace('{keyword}', $keyword, $template);
        }

        return $titles;
    }
}
