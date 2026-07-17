<?php

namespace App\Services\GeoFlow;

use App\Models\DistributionChannel;
use App\Models\DistributionChannelSecret;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyRequestFactory
{
    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function request(DistributionChannel $channel, int $timeout = 30): PendingRequest
    {
        $channel->loadMissing('activeSecret');
        $secret = $channel->activeSecret;
        if (! $secret instanceof DistributionChannelSecret) {
            throw new RuntimeException('Shopify 渠道缺少 Admin API Access Token。');
        }

        $accessToken = $this->apiKeyCrypto->decrypt((string) $secret->secret_ciphertext);
        if ($accessToken === '') {
            throw new RuntimeException('Shopify Admin API Access Token 解密失败。');
        }

        return Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['X-Shopify-Access-Token' => $accessToken]);
    }
}
