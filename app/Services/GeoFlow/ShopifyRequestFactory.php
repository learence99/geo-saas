<?php

namespace App\Services\GeoFlow;

use App\Models\DistributionChannel;
use App\Models\DistributionChannelSecret;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyRequestFactory
{
    /**
     * Shopify Client Credentials Grant 令牌有效期 24 小时，提前 1 小时刷新以留出安全余量。
     */
    private const TOKEN_TTL_SECONDS = 23 * 3600;

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function request(DistributionChannel $channel, int $timeout = 30): PendingRequest
    {
        $channel->loadMissing('activeSecret');
        $secret = $channel->activeSecret;
        if (! $secret instanceof DistributionChannelSecret) {
            throw new RuntimeException('Shopify 渠道缺少 Admin API 凭据。');
        }

        $accessToken = $this->isClientCredentialsSecret($secret)
            ? $this->resolveTokenViaClientCredentials($channel, $secret)
            : $this->apiKeyCrypto->decrypt((string) $secret->secret_ciphertext);

        if ($accessToken === '') {
            throw new RuntimeException('Shopify Admin API 访问令牌获取失败。');
        }

        return Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['X-Shopify-Access-Token' => $accessToken]);
    }

    /**
     * 强制丢弃缓存中的令牌，下一次 request() 会重新走一遍 Client Credentials Grant 换取新令牌。
     * 用于遇到 401 时的一次性重试。
     */
    public function forgetCachedToken(DistributionChannel $channel): void
    {
        Cache::forget($this->tokenCacheKey($channel));
    }

    private function isClientCredentialsSecret(DistributionChannelSecret $secret): bool
    {
        return in_array('shopify.ccg', (array) ($secret->scopes ?? []), true);
    }

    private function extractClientId(DistributionChannelSecret $secret): string
    {
        foreach ((array) ($secret->scopes ?? []) as $scope) {
            if (is_string($scope) && str_starts_with($scope, 'client_id:')) {
                return substr($scope, strlen('client_id:'));
            }
        }

        return '';
    }

    private function resolveTokenViaClientCredentials(DistributionChannel $channel, DistributionChannelSecret $secret): string
    {
        return Cache::remember($this->tokenCacheKey($channel), self::TOKEN_TTL_SECONDS, function () use ($channel, $secret): string {
            $clientId = $this->extractClientId($secret);
            $clientSecret = $this->apiKeyCrypto->decrypt((string) $secret->secret_ciphertext);

            if ($clientId === '' || $clientSecret === '') {
                throw new RuntimeException('Shopify Client ID / Client Secret 缺失。');
            }

            $tokenUrl = rtrim($channel->endpoint_url, '/').'/admin/oauth/access_token';

            $response = Http::timeout(15)
                ->asForm()
                ->acceptJson()
                ->post($tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->failed()) {
                $body = $response->json();
                $summary = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : trim((string) $response->body());
                throw new RuntimeException('Shopify Client Credentials 换取访问令牌失败：HTTP '.$response->status().($summary !== '' ? ' '.$summary : ''));
            }

            $token = trim((string) $response->json('access_token'));
            if ($token === '') {
                throw new RuntimeException('Shopify Client Credentials 响应中缺少 access_token。');
            }

            return $token;
        });
    }

    private function tokenCacheKey(DistributionChannel $channel): string
    {
        return 'geoflow:shopify_ccg_token:'.$channel->id;
    }
}
