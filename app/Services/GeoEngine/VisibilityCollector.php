<?php

namespace App\Services\GeoEngine;

use Illuminate\Support\Facades\Http;

/**
 * AI 可见度采集器（MVP）。放置：项目根 /app/Services/GeoEngine/VisibilityCollector.php
 * 流程：拿 prompt 去问某个 AI 引擎 → 抓答案 → 用裁判模型解析"有没有引用主体/排第几/竞品/情感"。
 */
class VisibilityCollector
{
    public static function engines(): array
    {
        return config('geo_engines.engines', []);
    }

    /** 对单个 prompt 在单个引擎上做一次可见度检查 */
    public static function check(string $prompt, string $subject, string $engineKey): array
    {
        $engines = self::engines();
        $eng = $engines[$engineKey] ?? null;
        if (!$eng) {
            throw new \RuntimeException("引擎不存在：$engineKey");
        }
        if (empty($eng['key'])) {
            throw new \RuntimeException("引擎「{$eng['name']}」未配置 API key（在 .env 填上对应 key）");
        }
        if (empty($eng['model'])) {
            throw new \RuntimeException("引擎「{$eng['name']}」未配置 model（在 .env 填上模型/接入点ID）");
        }

        // 1) 扮演用户提问，拿到引擎的"推荐型"答案
        $answer = self::chat($eng, [
            ['role' => 'system', 'content' => '你是一个会给出具体推荐的智能助手。回答用户问题时，如果涉及选择/推荐，请像真实回答一样给出具体的品牌、服务或方案名称。'],
            ['role' => 'user', 'content' => $prompt],
        ], 0.4, false);

        // 2) 裁判解析
        $judge = config('geo_engines.judge');
        $judgePrompt = "下面是 AI 引擎对一个问题的回答。请判断品牌/主体「{$subject}」在这段回答中的可见度，只输出合法 JSON，不要任何多余文字：\n"
            . '{"is_cited": true或false, "rank": 数字或null（若主体被提及/推荐，在所有被提及的品牌或选项中大致排第几，1为最靠前；未提及填null）, '
            . '"competitors": [回答中提到的其他品牌或服务名称数组], "sentiment": "positive或neutral或negative或none"}' . "\n\n回答内容：\n" . $answer;
        $raw = self::chat([
            'base_url' => $judge['base_url'], 'model' => $judge['model'], 'key' => $judge['key'],
        ], [
            ['role' => 'user', 'content' => $judgePrompt],
        ], 0.1, true);

        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            $parsed = ['is_cited' => false, 'rank' => null, 'competitors' => [], 'sentiment' => 'none'];
        }

        return [
            'engine' => $engineKey,
            'is_cited' => (bool) ($parsed['is_cited'] ?? false),
            'rank' => isset($parsed['rank']) && is_numeric($parsed['rank']) ? (int) $parsed['rank'] : null,
            'competitors' => array_values(array_filter((array) ($parsed['competitors'] ?? []))),
            'sentiment' => (string) ($parsed['sentiment'] ?? 'none'),
            'answer' => $answer,
        ];
    }

    private static function chat(array $eng, array $messages, float $temp, bool $json): string
    {
        $body = [
            'model' => $eng['model'],
            'temperature' => $temp,
            'messages' => $messages,
        ];
        if ($json) {
            $body['response_format'] = ['type' => 'json_object'];
        }
        $resp = Http::withToken($eng['key'])->timeout(120)
            ->post(rtrim($eng['base_url'], '/') . '/chat/completions', $body);
        if (!$resp->ok()) {
            throw new \RuntimeException('引擎调用失败：HTTP ' . $resp->status() . ' ' . mb_substr($resp->body(), 0, 200));
        }
        return (string) ($resp->json('choices.0.message.content') ?? '');
    }
}
