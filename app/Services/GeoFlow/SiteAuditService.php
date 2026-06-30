<?php

namespace App\Services\GeoFlow;

use Illuminate\Support\Facades\Http;

/**
 * 站点体检（A 硬检查 + B GEO 检查，纯 PHP，零 AI 成本）。
 * 放置：app/Services/GeoFlow/SiteAuditService.php
 *
 * 流程：抓 URL 的 HTML → DOM 解析 → 跑 SEO 基础 + GEO/AI 可见 两组规则 → 出问题清单 + 评分。
 * 不依赖任何外部付费服务，不调 AI；robots/sitemap/llms.txt 各发一个轻量 HTTP 请求。
 */
class SiteAuditService
{
    /** 这些 AI 爬虫被 robots 挡住 = AI 看不到你（GEO 关键）。*/
    private const AI_BOTS = ['GPTBot', 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot', 'Claude-Web', 'Google-Extended', 'CCBot', 'Bytespider', 'Amazonbot'];

    /**
     * 体检入口。返回结构化报告（供视图/JSON 渲染）。
     *
     * @return array{url:string,ok:bool,error?:string,score:int,summary:array,groups:array}
     */
    public function audit(string $rawUrl): array
    {
        $url = $this->normalizeUrl($rawUrl);
        if ($url === '') {
            return ['url' => $rawUrl, 'ok' => false, 'error' => '网址无效，请输入形如 https://example.com 的地址', 'score' => 0, 'summary' => [], 'groups' => []];
        }
        $origin = $this->origin($url);

        // 1) 抓主页面 HTML
        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; GEO-SaaS-Audit/1.0)',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->connectTimeout(5)->timeout(9)->get($url);
        } catch (\Throwable $e) {
            return ['url' => $url, 'ok' => false, 'error' => '无法访问该网址：' . $e->getMessage(), 'score' => 0, 'summary' => [], 'groups' => []];
        }
        if (!$resp->ok()) {
            return ['url' => $url, 'ok' => false, 'error' => '该网址返回 HTTP ' . $resp->status() . '，无法体检', 'score' => 0, 'summary' => [], 'groups' => []];
        }
        $html = (string) $resp->body();
        // 重页面（如 baidu 首页）HTML 极大、DOM 解析很慢——截断到合理上限，足够覆盖 head 与主体结构
        if (strlen($html) > 700000) {
            $html = substr($html, 0, 700000);
        }

        // 2) DOM 解析
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        // 3) 旁路资源（robots / llms.txt / sitemap.xml）——并发抓取，把 3 次串行压成 1 次
        $ua = ['User-Agent' => 'GEO-SaaS-Audit/1.0'];
        $pool = Http::pool(fn ($p) => [
            $p->as('robots')->connectTimeout(3)->timeout(4)->withHeaders($ua)->get($origin . '/robots.txt'),
            $p->as('llms')->connectTimeout(3)->timeout(4)->withHeaders($ua)->get($origin . '/llms.txt'),
            $p->as('sitemap')->connectTimeout(3)->timeout(4)->withHeaders($ua)->get($origin . '/sitemap.xml'),
        ]);
        $robots = $this->bodyOf($pool['robots'] ?? null);
        $hasLlms = $this->exists($pool['llms'] ?? null);
        $sitemapInRobots = $robots !== null && preg_match('/^\s*sitemap:/im', $robots) === 1;
        $hasSitemap = $sitemapInRobots || $this->exists($pool['sitemap'] ?? null);
        $blockedBots = $robots !== null ? $this->blockedAiBots($robots) : [];

        // 4) 跑检查
        $seo = $this->seoChecks($xp, $html);
        $geo = $this->geoChecks($xp, $html, $robots, $hasLlms, $hasSitemap, $blockedBots);

        $groups = [
            ['key' => 'seo', 'label' => 'SEO 基础', 'items' => $seo],
            ['key' => 'geo', 'label' => 'GEO / AI 可见', 'items' => $geo],
        ];

        return [
            'url' => $url,
            'ok' => true,
            'score' => $this->score($groups),
            'summary' => $this->summary($groups),
            'groups' => $groups,
        ];
    }

    // ===================== A. SEO 基础 =====================
    private function seoChecks(\DOMXPath $xp, string $html): array
    {
        $items = [];

        // 标题
        $title = trim($this->firstText($xp, '//title'));
        $len = mb_strlen($title);
        $items[] = $title === ''
            ? $this->item('title', '页面标题（title）', 'error', '缺少 <title>', '加上一个包含核心词的标题，10–60 字为宜')
            : $this->item('title', '页面标题（title）', ($len >= 10 && $len <= 60) ? 'pass' : 'warn', "「{$title}」（{$len} 字）", $len < 10 ? '标题偏短，补充核心词' : ($len > 60 ? '标题偏长，搜索/AI 可能截断' : '长度合适'));

        // 描述
        $desc = trim($this->attr($xp, '//meta[@name="description"]', 'content'));
        $dlen = mb_strlen($desc);
        $items[] = $desc === ''
            ? $this->item('desc', 'Meta 描述', 'error', '缺少 meta description', '加一段 50–160 字的页面摘要，AI/搜索常用它做摘要')
            : $this->item('desc', 'Meta 描述', ($dlen >= 50 && $dlen <= 160) ? 'pass' : 'warn', "{$dlen} 字", $dlen < 50 ? '描述偏短' : ($dlen > 160 ? '描述偏长会被截断' : '长度合适'));

        // H1
        $h1 = $xp->query('//h1')->length;
        $items[] = $h1 === 1
            ? $this->item('h1', 'H1 主标题', 'pass', '恰好 1 个', 'H1 唯一，结构清晰')
            : $this->item('h1', 'H1 主标题', $h1 === 0 ? 'error' : 'warn', $h1 . ' 个', $h1 === 0 ? '页面缺少 H1' : '存在多个 H1，建议只保留一个主标题');

        // 图片 alt
        $imgTotal = $xp->query('//img')->length;
        $imgNoAlt = $xp->query('//img[not(@alt) or normalize-space(@alt)=""]')->length;
        $items[] = $imgTotal === 0
            ? $this->item('img', '图片 alt', 'pass', '无图片', '无需处理')
            : $this->item('img', '图片 alt', $imgNoAlt === 0 ? 'pass' : 'warn', "{$imgNoAlt}/{$imgTotal} 张缺 alt", $imgNoAlt === 0 ? '全部有 alt' : '给图片补 alt，利于无障碍与图片搜索');

        // canonical
        $canonical = $this->attr($xp, '//link[@rel="canonical"]', 'href');
        $items[] = $this->item('canonical', 'Canonical 规范链接', $canonical !== '' ? 'pass' : 'warn', $canonical !== '' ? $canonical : '未设置', $canonical !== '' ? '已声明规范 URL' : '建议加 canonical，避免重复内容分散权重');

        // viewport（移动端）
        $viewport = $this->attr($xp, '//meta[@name="viewport"]', 'content');
        $items[] = $this->item('viewport', '移动端适配（viewport）', $viewport !== '' ? 'pass' : 'warn', $viewport !== '' ? '已设置' : '缺少 viewport', $viewport !== '' ? '移动端可正常缩放' : '加 viewport meta，移动端体验与排名相关');

        // Open Graph
        $ogTitle = $this->attr($xp, '//meta[@property="og:title"]', 'content');
        $ogImage = $this->attr($xp, '//meta[@property="og:image"]', 'content');
        $okOg = $ogTitle !== '' && $ogImage !== '';
        $items[] = $this->item('og', 'Open Graph（社交/分享卡片）', $okOg ? 'pass' : 'warn', $okOg ? '完整' : '缺少 og:title 或 og:image', $okOg ? '分享时有标题图' : '补 og:title/og:image，分享和部分 AI 抓取更友好');

        // html lang
        $lang = $this->attr($xp, '//html', 'lang');
        $items[] = $this->item('lang', '语言声明（html lang）', $lang !== '' ? 'pass' : 'warn', $lang !== '' ? $lang : '未声明', $lang !== '' ? '已声明语言' : '给 <html> 加 lang，利于多语言与抓取');

        return $items;
    }

    // ===================== B. GEO / AI 可见 =====================
    private function geoChecks(\DOMXPath $xp, string $html, ?string $robots, bool $hasLlms, bool $hasSitemap, array $blockedBots): array
    {
        $items = [];

        // 结构化数据（Schema.org JSON-LD）
        $ld = $xp->query('//script[@type="application/ld+json"]')->length;
        $items[] = $this->item('schema', '结构化数据（Schema.org）', $ld > 0 ? 'pass' : 'warn', $ld > 0 ? "{$ld} 段 JSON-LD" : '未发现', $ld > 0 ? 'AI/搜索更易理解与引用' : '加 JSON-LD（如 Article/FAQ），显著提升被 AI 引用概率');

        // AI 爬虫可访问性
        if ($robots === null) {
            $items[] = $this->item('aibots', 'AI 爬虫可访问性', 'pass', '无 robots.txt（默认全允许）', 'AI 爬虫未被限制');
        } elseif (count($blockedBots) === 0) {
            $items[] = $this->item('aibots', 'AI 爬虫可访问性', 'pass', '未屏蔽主流 AI 爬虫', 'GPTBot/PerplexityBot 等可抓取你的内容');
        } else {
            $items[] = $this->item('aibots', 'AI 爬虫可访问性', 'error', '被屏蔽：' . implode('、', $blockedBots), '这些 AI 爬虫被 robots.txt 挡住 = AI 引擎看不到你的内容，GEO 直接归零，建议放开');
        }

        // llms.txt
        $items[] = $this->item('llms', 'llms.txt（AI 索引清单）', $hasLlms ? 'pass' : 'warn', $hasLlms ? '已提供' : '未提供', $hasLlms ? '已为 AI 提供内容清单' : '加 /llms.txt，主动告诉 AI 你有哪些可引用内容（GEO 加分项）');

        // sitemap
        $items[] = $this->item('sitemap', 'Sitemap 站点地图', $hasSitemap ? 'pass' : 'warn', $hasSitemap ? '已提供' : '未发现', $hasSitemap ? '收录入口完整' : '加 sitemap.xml，帮助搜索/AI 发现全部页面');

        // 内容可引用性（粗判：正文体量 + 小标题 + 列表/问答结构）
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
        $words = mb_strlen($text);
        $h2 = $xp->query('//h2')->length + $xp->query('//h3')->length;
        $lists = $xp->query('//ul | //ol | //table')->length;
        if ($words < 300) {
            $items[] = $this->item('citable', '内容可引用性', 'error', "正文偏少（约 {$words} 字）", '内容太薄，AI 没有可引用的实质段落，建议用关键词库+内容引擎扩充');
        } else {
            $struct = $h2 >= 2 && $lists >= 1;
            $items[] = $this->item('citable', '内容可引用性', $struct ? 'pass' : 'warn', "约 {$words} 字 · {$h2} 个小标题 · {$lists} 个列表/表格", $struct ? '结构清晰，便于 AI 摘录引用' : '加小标题/列表/问答结构，让 AI 更容易截取你的段落作答');
        }

        return $items;
    }

    // ===================== 解析 robots：哪些 AI 爬虫被禁 =====================
    private function blockedAiBots(string $robots): array
    {
        // 按 User-agent 分块：连续的 User-agent 行共享后续规则；一旦出现规则行，下一个
        // User-agent 行即开启新组。记录每个 UA 组是否 Disallow: /（= 全站屏蔽）。
        $lines = preg_split('/\r\n|\r|\n/', $robots);
        $current = [];          // 当前组的 UA 列表（小写）
        $blocks = [];           // ua(lower) => bool 是否被 Disallow: /
        $disallowAll = false;   // 当前组是否 Disallow: /
        $sawRule = false;       // 当前组自上个 UA 后是否已出现规则行
        $flush = function () use (&$current, &$blocks, &$disallowAll) {
            foreach ($current as $ua) {
                $blocks[$ua] = ($blocks[$ua] ?? false) || $disallowAll;
            }
        };
        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if ($line === '') {
                continue;
            }
            if (preg_match('/^user-agent:\s*(.+)$/i', $line, $m)) {
                if ($sawRule) {            // 上一组规则已结束 → 结算并开新组
                    $flush();
                    $current = [];
                    $disallowAll = false;
                    $sawRule = false;
                }
                $current[] = strtolower(trim($m[1]));
                continue;
            }
            // 任何非 User-agent 的指令行都算"规则"
            $sawRule = true;
            if (preg_match('/^disallow:\s*(.*)$/i', $line, $m) && trim($m[1]) === '/') {
                $disallowAll = true;
            }
        }
        $flush();

        $blocked = [];
        foreach (self::AI_BOTS as $bot) {
            $b = strtolower($bot);
            if (($blocks[$b] ?? false) || ($blocks['*'] ?? false)) {
                $blocked[] = $bot;
            }
        }
        return $blocked;
    }

    // ===================== 评分 / 汇总 =====================
    private function score(array $groups): int
    {
        $w = ['pass' => 1.0, 'warn' => 0.5, 'error' => 0.0];
        $sum = 0.0;
        $n = 0;
        foreach ($groups as $g) {
            foreach ($g['items'] as $it) {
                $sum += $w[$it['status']] ?? 0;
                $n++;
            }
        }
        return $n > 0 ? (int) round($sum / $n * 100) : 0;
    }

    private function summary(array $groups): array
    {
        $s = ['pass' => 0, 'warn' => 0, 'error' => 0];
        foreach ($groups as $g) {
            foreach ($g['items'] as $it) {
                $s[$it['status']]++;
            }
        }
        return $s;
    }

    // ===================== 工具 =====================
    private function item(string $key, string $label, string $status, string $detail, string $fix): array
    {
        return compact('key', 'label', 'status', 'detail', 'fix');
    }

    private function firstText(\DOMXPath $xp, string $q): string
    {
        $n = $xp->query($q)->item(0);
        return $n ? $n->textContent : '';
    }

    private function attr(\DOMXPath $xp, string $q, string $attr): string
    {
        $n = $xp->query($q)->item(0);
        return $n instanceof \DOMElement ? trim($n->getAttribute($attr)) : '';
    }

    private function normalizeUrl(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $raw)) {
            $raw = 'https://' . $raw;
        }
        return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : '';
    }

    private function origin(string $url): string
    {
        $p = parse_url($url);
        if (!isset($p['scheme'], $p['host'])) {
            return rtrim($url, '/');
        }
        $origin = $p['scheme'] . '://' . $p['host'];
        if (isset($p['port'])) {
            $origin .= ':' . $p['port'];
        }
        return $origin;
    }

    /** Http::pool 里失败的请求会返回异常对象而非抛出，这里安全取值。*/
    private function bodyOf($resp): ?string
    {
        return $resp instanceof \Illuminate\Http\Client\Response && $resp->ok() ? (string) $resp->body() : null;
    }

    private function exists($resp): bool
    {
        return $resp instanceof \Illuminate\Http\Client\Response && $resp->ok() && trim((string) $resp->body()) !== '';
    }
}
