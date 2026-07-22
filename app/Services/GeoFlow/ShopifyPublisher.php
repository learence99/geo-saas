<?php

namespace App\Services\GeoFlow;

use App\Models\ArticleDistribution;
use App\Models\DistributionChannel;
use Illuminate\Http\Client\Response;
use RuntimeException;

class ShopifyPublisher implements DistributionPublisherInterface
{
    public function __construct(private readonly ShopifyRequestFactory $requestFactory) {}

    public function health(DistributionChannel $channel): array
    {
        $config = $channel->resolvedShopifyConfig();

        $shopResponse = $this->send($channel, fn ($request) => $request->get($channel->shopifyApiBaseUrl().'/shop.json'), 10);
        $this->throwIfFailed($shopResponse, 'Shopify 店铺信息检测');
        $shop = $shopResponse->json('shop');
        $shop = is_array($shop) ? $shop : [];

        $blogOk = false;
        $blogName = '';
        if ($config['shopify_blog_id'] !== '') {
            $blogResponse = $this->send($channel, fn ($request) => $request->get($channel->shopifyApiBaseUrl().'/blogs/'.$config['shopify_blog_id'].'.json'), 10);
            $blogOk = ! $blogResponse->failed();
            $blog = $blogResponse->json('blog');
            $blogName = is_array($blog) ? (string) ($blog['title'] ?? '') : '';
        }

        return [
            'ok' => true,
            'channel_type' => 'shopify',
            'shop_name' => (string) ($shop['name'] ?? ''),
            'shop_domain' => (string) ($shop['myshopify_domain'] ?? ''),
            'plan_name' => (string) ($shop['plan_name'] ?? ''),
            'blog_id' => $config['shopify_blog_id'],
            'blog_found' => $blogOk,
            'blog_name' => $blogName,
        ];
    }

    public function publish(ArticleDistribution $distribution, array $payload): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $config = $channel->resolvedShopifyConfig();
        $this->assertBlogConfigured($config);

        $response = $this->send($channel, fn ($request) => $request->post(
            $channel->shopifyApiBaseUrl().'/blogs/'.$config['shopify_blog_id'].'/articles.json',
            ['article' => $this->articlePayload($channel, $payload)]
        ));
        $this->throwIfFailed($response, 'Shopify 文章发布');

        return $this->articleResult($channel, $config, $response);
    }

    public function update(ArticleDistribution $distribution, array $payload): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $config = $channel->resolvedShopifyConfig();
        $this->assertBlogConfigured($config);

        $articleId = $distribution->shopifyArticleId();
        if (! $articleId) {
            return $this->publish($distribution, $payload);
        }

        $response = $this->send($channel, fn ($request) => $request->put(
            $channel->shopifyApiBaseUrl().'/blogs/'.$config['shopify_blog_id'].'/articles/'.$articleId.'.json',
            ['article' => $this->articlePayload($channel, $payload)]
        ));
        $this->throwIfFailed($response, 'Shopify 文章更新');

        return $this->articleResult($channel, $config, $response);
    }

    public function delete(ArticleDistribution $distribution): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $config = $channel->resolvedShopifyConfig();
        $articleId = $distribution->shopifyArticleId();

        if (! $articleId || $config['shopify_blog_id'] === '') {
            return [
                'deleted' => true,
                'remote_id' => null,
                'remote_url' => null,
                'message' => 'missing_remote_article_id',
            ];
        }

        $response = $this->send($channel, fn ($request) => $request->delete(
            $channel->shopifyApiBaseUrl().'/blogs/'.$config['shopify_blog_id'].'/articles/'.$articleId.'.json'
        ));
        $this->throwIfFailed($response, 'Shopify 文章删除');

        return [
            'deleted' => true,
            'remote_id' => (string) $articleId,
            'remote_url' => null,
        ];
    }

    /**
     * Shopify 没有和 GEOFlow Agent/WordPress 对等的"站点设置"概念（主题、首页由 Shopify 自己的店铺后台管理），
     * 这里直接跳过，避免误以为能同步网站基础信息到 Shopify 店铺。
     */
    public function syncSiteSettings(DistributionChannel $channel): array
    {
        return [
            'ok' => true,
            'skipped' => true,
            'message' => 'Shopify 渠道不支持站点设置同步，博客文章内容以外的店铺设置请直接在 Shopify 后台维护。',
        ];
    }

    /**
     * 统一发起请求；若使用 Client Credentials Grant 的令牌已过期（Shopify 返回 401），
     * 丢弃缓存后重试一次，避免 24 小时令牌到期窗口造成误报失败。
     */
    private function send(DistributionChannel $channel, callable $call, int $timeout = 30): Response
    {
        $response = $call($this->requestFactory->request($channel, $timeout));

        if ($response->status() === 401) {
            $this->requestFactory->forgetCachedToken($channel);
            $response = $call($this->requestFactory->request($channel, $timeout));
        }

        return $response;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function articlePayload(DistributionChannel $channel, array $payload): array
    {
        $article = is_array($payload['article'] ?? null) ? $payload['article'] : [];
        $config = $channel->resolvedShopifyConfig();

        $articlePayload = [
            'title' => (string) ($article['title'] ?? ''),
            'body_html' => (string) ($article['content_html'] ?? ''),
            'author' => (string) ($article['author']['name'] ?? '') ?: (string) $channel->name,
            'published' => $config['shopify_post_status'] === 'published',
        ];

        $heroImageUrl = trim((string) ($article['hero_image_url'] ?? ''));
        if ($heroImageUrl !== '') {
            $articlePayload['image'] = ['src' => $heroImageUrl];
        }

        if ($config['shopify_tag_strategy'] === 'keywords_to_tags') {
            $tags = $this->buildTags($article);
            if ($tags !== '') {
                $articlePayload['tags'] = $tags;
            }
        }

        return $articlePayload;
    }

    /**
     * @param  array<string,mixed>  $article
     */
    private function buildTags(array $article): string
    {
        $raw = (string) ($article['keywords'] ?? '');
        $parts = preg_split('/[,，、\s]+/u', $raw) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || in_array($part, $tags, true)) {
                continue;
            }
            $tags[] = $part;
        }

        $categoryName = trim((string) ($article['category']['name'] ?? ''));
        if ($categoryName !== '' && ! in_array($categoryName, $tags, true)) {
            $tags[] = $categoryName;
        }

        return implode(', ', array_slice($tags, 0, 20));
    }

    private function assertBlogConfigured(array $config): void
    {
        if ($config['shopify_blog_id'] === '') {
            throw new RuntimeException('Shopify 渠道尚未配置目标 Blog ID。');
        }
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array<string,mixed>
     */
    private function articleResult(DistributionChannel $channel, array $config, Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Shopify 返回内容不是有效 JSON。');
        }

        $resultArticle = is_array($json['article'] ?? null) ? $json['article'] : [];
        $articleId = (int) ($resultArticle['id'] ?? 0);
        $shopDomain = rtrim(str_replace(['https://', 'http://'], '', $channel->endpoint_url), '/');

        return [
            'remote_id' => $articleId > 0 ? (string) $articleId : '',
            'remote_url' => $articleId > 0
                ? 'https://'.$shopDomain.'/admin/blogs/'.$config['shopify_blog_id'].'/articles/'.$articleId
                : '',
            'remote_meta' => [
                'shopify_article_id' => $articleId,
                'shopify_blog_id' => (int) $config['shopify_blog_id'],
                'shopify_handle' => (string) ($resultArticle['handle'] ?? ''),
            ],
        ];
    }

    private function channel(ArticleDistribution $distribution): DistributionChannel
    {
        if (! $distribution->channel instanceof DistributionChannel) {
            throw new RuntimeException('分发记录缺少 Shopify 渠道。');
        }

        return $distribution->channel;
    }

    private function throwIfFailed(Response $response, string $operation): void
    {
        if (! $response->failed()) {
            return;
        }

        $body = $response->json();
        $summary = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : trim((string) $response->body());
        $summary = is_string($summary) && mb_strlen($summary) > 300 ? mb_substr($summary, 0, 300).'...' : (string) $summary;

        throw new RuntimeException($operation.'失败：HTTP '.$response->status().($summary !== '' ? ' '.$summary : ''));
    }
}
