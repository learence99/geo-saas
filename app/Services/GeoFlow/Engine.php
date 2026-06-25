<?php

namespace App\Services\GeoFlow;

use Illuminate\Support\Facades\Http;

/**
 * 多行业 GEO 问题集群生成引擎（自包含，不依赖 GEOFlow 内部）。
 * 放置：项目根 /app/Services/GeoEngine/Engine.php
 * 流程：路由核心词 → 拼三层 prompt → 调 DeepSeek → 解析 → 通用校验 → 修复 loop → 返回。
 * 行业相关的一切来自 config/geo_packs.php（数据），引擎本身行业无关。
 */
class Engine
{
    public static function packs(): array
    {
        return config('geo_packs', []);
    }

    public static function pack(string $slug): ?array
    {
        return config("geo_packs.$slug");
    }

    /** 主入口 */
    public static function generate(string $keyword, int $countPerStage, string $packSlug, string $subject = '', array $tenant = []): array
    {
        $cfg = config('geoengine');
        $pack = self::pack($packSlug);
        if (!$pack) {
            throw new \RuntimeException("行业包不存在：$packSlug");
        }

        $subject = trim($subject) !== '' ? trim($subject) : ($cfg['default_subject'] ?? '');
        $tenant = array_merge([
            'subject_name' => $subject,
            'subject_aliases' => [],
            'competitor_blocklist' => [],
        ], $tenant);
        $tenant['subject_name'] = $subject;

        $type = self::route($keyword, $pack['routing_rules']);
        $prompt = self::buildPrompt($cfg, $pack, $type, $subject, $keyword, $countPerStage);

        $raw = self::callModel($cfg, $prompt, $keyword);
        $items = self::parse($raw);
        $report = self::validate($items, $pack, $tenant, $countPerStage);

        $tries = 0;
        $maxTry = $pack['thresholds']['repair_max_retry'] ?? 2;
        while (!$report['ok'] && $tries < $maxTry) {
            $tries++;
            $raw = self::callModel($cfg, $prompt . "\n\n" . self::repairNote($report), $keyword);
            $items = self::parse($raw);
            $report = self::validate($items, $pack, $tenant, $countPerStage);
        }

        return [
            'keyword' => $keyword,
            'pack' => $packSlug,
            'pack_name' => $pack['name'] ?? $packSlug,
            'subject' => $subject,
            'routedType' => $type,
            'items' => $items,
            'report' => $report,
            'repairTries' => $tries,
        ];
    }

    private static function route(string $kw, array $rules): string
    {
        $fallback = 'default';
        foreach ($rules as $r) {
            if (($r['match'] ?? '') === '*') {
                $fallback = $r['type'];
                continue;
            }
            if (@preg_match('/' . $r['match'] . '/u', $kw)) {
                return $r['type'];
            }
        }
        return $fallback;
    }

    private static function buildPrompt(array $cfg, array $pack, string $type, string $subject, string $kw, int $count): string
    {
        $stages = implode('、', array_map(fn ($s) => $s['key'], $pack['stages']));
        $types = implode('、', $pack['taxonomy']['types']);
        $intents = implode('、', $pack['taxonomy']['intents']);

        $base = strtr($cfg['base_rules'], [
            '{{subject}}' => $subject,
            '{{stages}}' => $stages,
            '{{types}}' => $types,
            '{{intents}}' => $intents,
        ]);

        $strategy = $pack['strategies'][$type] ?? ($pack['strategies']['default'] ?? '');
        $th = $pack['thresholds'];

        $stageList = '';
        foreach ($pack['stages'] as $s) {
            $stageList .= "- {$s['key']}（倾向 " . implode('/', $s['intent_bias']) . "）\n";
        }

        $comp = '';
        if (!empty($pack['compliance']['banned_claims'])) {
            $comp = "禁止出现承诺/夸大词：" . implode('、', $pack['compliance']['banned_claims']) . "。\n";
        }

        $task = "【本次任务】\n"
            . "核心词：{$kw}\n"
            . "主体：{$subject}\n"
            . "每阶段生成数量：{$count}\n"
            . "阶段：\n{$stageList}\n"
            . "要求：\n"
            . "1. 每个阶段恰好生成 {$count} 条。\n"
            . "2. merchantVersion 字数 {$th['merchant_len'][0]}–{$th['merchant_len'][1]} 个中文字符。\n"
            . "3. MEDIUM+STRONG 占比 ≥ " . intval($th['min_commercial_ratio'] * 100) . "%。\n"
            . "4. KNOWLEDGE 全局 ≤ {$th['max_knowledge']} 条。\n"
            . $comp
            . "5. 只输出合法 JSON：{\"items\": []}";

        return $base . "\n\n" . $strategy . "\n\n" . $task;
    }

    private static function callModel(array $cfg, string $systemPrompt, string $kw): string
    {
        $key = $cfg['api_key'] ?: '';
        if ($key === '') {
            throw new \RuntimeException("未配置 {$cfg['key_env']}：请在项目根 .env 填入 DeepSeek key,再执行 php artisan optimize:clear。");
        }

        $resp = Http::withToken($key)
            ->timeout(120)
            ->post(rtrim($cfg['base_url'], '/') . '/chat/completions', [
                'model' => $cfg['model'],
                'temperature' => $cfg['temperature'] ?? 0.5,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "请生成核心词「{$kw}」的 GEO 用户问题集群。"],
                ],
            ]);

        if (!$resp->ok()) {
            throw new \RuntimeException("模型调用失败：HTTP {$resp->status()} " . mb_substr($resp->body(), 0, 300));
        }

        return (string) ($resp->json('choices.0.message.content') ?? '');
    }

    private static function parse(string $raw): array
    {
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            throw new \RuntimeException('模型返回不是合法的 {"items":[]} 结构');
        }
        return $data['items'];
    }

    private static function validate(array $items, array $pack, array $tenant, int $count): array
    {
        $stages = array_map(fn ($s) => $s['key'], $pack['stages']);
        $types = $pack['taxonomy']['types'];
        $intents = $pack['taxonomy']['intents'];
        $th = $pack['thresholds'];
        $lex = $pack['lexicon'];
        $comp = $pack['compliance'];
        $required = ['stage', 'type', 'intent', 'text', 'merchantVersion', 'rationale'];

        $itemErrors = [];
        $batch = [];

        foreach ($items as $i => $it) {
            $errs = [];
            foreach ($required as $f) {
                if (!isset($it[$f]) || !is_string($it[$f]) || trim($it[$f]) === '') {
                    $errs[] = "缺少或为空：$f";
                }
            }
            if ($errs) {
                $itemErrors[$i] = $errs;
                continue;
            }

            $mv = $it['merchantVersion'];

            if (!in_array($it['stage'], $stages, true)) $errs[] = "stage 非法：{$it['stage']}";
            if (!in_array($it['type'], $types, true)) $errs[] = "type 非法：{$it['type']}";
            if (!in_array($it['intent'], $intents, true)) $errs[] = "intent 非法：{$it['intent']}";

            if ($it['type'] === 'BRAND' && mb_strpos($mv, $tenant['subject_name']) === false) {
                $errs[] = "BRAND 未含主体「{$tenant['subject_name']}」";
            }
            foreach (($tenant['subject_aliases'] ?? []) as $al) {
                if ($al !== '' && mb_strpos($mv, $al) !== false && mb_strpos($mv, $tenant['subject_name']) === false) {
                    $errs[] = "使用了禁止简称「$al」";
                }
            }
            foreach (array_merge($lex['third_party'] ?? [], $tenant['competitor_blocklist'] ?? []) as $w) {
                if ($w !== '' && (mb_strpos($mv, $w) !== false || mb_strpos($it['text'], $w) !== false)) {
                    $errs[] = "出现第三方/竞品词「$w」";
                }
            }
            foreach (($lex['forbidden'] ?? []) as $w) {
                if ($w !== '' && mb_strpos($mv, $w) !== false) $errs[] = "禁用词「$w」";
            }
            foreach (($lex['ending_words'] ?? []) as $w) {
                if ($w !== '' && mb_substr($mv, -mb_strlen($w)) === $w) $errs[] = "栏目化结尾「$w」";
            }
            $hasQ = false;
            foreach (($lex['question_words'] ?? []) as $q) {
                if (mb_strpos($mv, $q) !== false) { $hasQ = true; break; }
            }
            if (!$hasQ) $errs[] = '缺少疑问语气';
            if (mb_substr_count($mv, '？') > 1) $errs[] = '问号超过 1 个';

            $len = mb_strlen($mv);
            if ($len < $th['merchant_len'][0] || $len > $th['merchant_len'][1]) {
                $errs[] = "标题字数 $len 超出 {$th['merchant_len'][0]}-{$th['merchant_len'][1]}";
            }
            foreach (($comp['banned_claims'] ?? []) as $w) {
                if ($w !== '' && (mb_strpos($mv, $w) !== false || mb_strpos($it['text'], $w) !== false)) {
                    $errs[] = "合规禁词「$w」";
                }
            }

            if ($errs) $itemErrors[$i] = $errs;
        }

        $total = count($items);
        if ($total > 0) {
            foreach ($stages as $st) {
                $c = count(array_filter($items, fn ($x) => ($x['stage'] ?? '') === $st));
                if ($c !== $count) $batch[] = "「$st」数量 $c，应为 $count";
            }
            $know = count(array_filter($items, fn ($x) => ($x['intent'] ?? '') === 'KNOWLEDGE'));
            if ($know > $th['max_knowledge']) $batch[] = "KNOWLEDGE $know 条，超过上限 {$th['max_knowledge']}";
            $comm = count(array_filter($items, fn ($x) => in_array($x['intent'] ?? '', ['MEDIUM', 'STRONG'], true)));
            if ($comm / $total < $th['min_commercial_ratio']) {
                $batch[] = 'MEDIUM+STRONG 占比不足 ' . intval($th['min_commercial_ratio'] * 100) . '%';
            }
        } else {
            $batch[] = 'items 为空';
        }

        return [
            'ok' => empty($itemErrors) && empty($batch),
            'itemErrors' => $itemErrors,
            'batchErrors' => $batch,
            'total' => $total,
        ];
    }

    private static function repairNote(array $report): string
    {
        $lines = [];
        foreach ($report['batchErrors'] as $b) {
            $lines[] = "- 批次问题：$b";
        }
        foreach ($report['itemErrors'] as $i => $es) {
            $lines[] = '- 第' . ($i + 1) . '条：' . implode('；', $es);
        }
        return "【上次输出存在以下问题，请严格修正后重新输出完整合法 JSON（{\"items\":[]}）】\n" . implode("\n", $lines);
    }
}
