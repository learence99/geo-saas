<?php

namespace App\Services\GeoFlow;

/**
 * GEO 内容评分器（规则版，自包含）。
 * 放置：项目根 /app/Services/GeoEngine/GeoScorer.php
 * 对一段内容按"AI 是否爱引用"的维度打分 + 给优化清单。不依赖任何外部服务。
 */
class GeoScorer
{
    public static function score(string $content, string $keyword = ''): array
    {
        $c = $content;
        $len = mb_strlen(trim($c));
        $first = mb_substr($c, 0, 220);
        $kw = trim($keyword);

        $defs = [
            // [标题, 是否通过, 权重, 优化建议]
            [
                '首段直接回答(前220字命中核心)',
                $kw !== '' ? (mb_strpos($first, $kw) !== false) : (bool) preg_match('/[。！？]/u', $first),
                18,
                '把核心结论/核心词放进开头第一段，让 AI 一上来就能摘走答案',
            ],
            [
                '有可引用的精炼摘要',
                (bool) preg_match('/(核心摘要|摘要|总结|小结|简言之|一句话|TL;DR)/u', $c) || (bool) preg_match('/^\s*[-*]\s+/mu', $first),
                16,
                '开头加一段 3-5 条要点的"核心摘要"，便于 AI 整段引用',
            ],
            [
                '结构化小标题(H2/H3 ≥ 2)',
                preg_match_all('/^#{2,3}\s+/mu', $c) >= 2,
                14,
                '用 ## / ### 把正文切成清晰小节，AI 更容易定位段落',
            ],
            [
                '含 ≥ 3 处可验证数据/参数',
                preg_match_all('/\d+(\.\d+)?\s?(%|％|元|天|小时|年|月|公里|km|分钟|倍|万|亿|人)/u', $c) >= 3,
                14,
                '补充具体数字(价格/时长/比例)，AI 偏爱事实密集、可验证的内容',
            ],
            [
                '含 FAQ 问答结构',
                (bool) preg_match('/(FAQ|常见问题|Q\d|问[:：]|Q[:：])/u', $c),
                14,
                '加一组 2-4 条 FAQ，直接命中用户在 AI 里的真实提问',
            ],
            [
                '明确定义关键术语',
                (bool) preg_match('/(是指|是一种|指的是|定义为|所谓)/u', $c),
                10,
                '对核心概念给一句明确定义("XX 是指…")，提升被引用权威度',
            ],
            [
                '含对比表格',
                (bool) preg_match('/\|\s*-{2,}/u', $c),
                14,
                '加一个 Markdown 对比表，AI 爱引用结构化对比信息',
            ],
        ];

        $score = 0;
        $max = 0;
        $passed = 0;
        $rows = [];
        foreach ($defs as [$label, $ok, $weight, $tip]) {
            $ok = (bool) $ok;
            $max += $weight;
            if ($ok) {
                $score += $weight;
                $passed++;
            }
            $rows[] = ['label' => $label, 'ok' => $ok, 'weight' => $weight, 'tip' => $ok ? '' : $tip];
        }

        $final = $max > 0 ? (int) round($score / $max * 100) : 0;
        $grade = $final >= 80 ? '优秀' : ($final >= 60 ? '良好' : ($final >= 40 ? '待改进' : '较弱'));

        return [
            'score' => $final,
            'grade' => $grade,
            'passed' => $passed,
            'total' => count($defs),
            'wordCount' => $len,
            'checks' => $rows,
        ];
    }
}
