<?php

namespace App\Support\Unsplash;

use App\Models\SiteSetting;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 轻量 Unsplash 官方 API 封装（api.unsplash.com，非已停用的 source.unsplash.com）。
 */
class UnsplashClient
{
    private const SETTING_KEY = 'unsplash_access_key';

    private const API_BASE = 'https://api.unsplash.com';

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function hasAccessKey(): bool
    {
        return $this->accessKey() !== '';
    }

    /**
     * 按关键词搜索一张横版图片，返回图片 URL、署名信息与 download_location（用于下载打点）。
     *
     * @return array{url: string, thumb_url: string, credit_name: string, credit_url: string, download_location: string}|null
     */
    public function searchOne(string $query): ?array
    {
        $accessKey = $this->accessKey();
        if ($accessKey === '' || trim($query) === '') {
            return null;
        }

        $response = Http::withHeaders(['Authorization' => 'Client-ID '.$accessKey])
            ->timeout(15)
            ->get(self::API_BASE.'/search/photos', [
                'query' => $query,
                'per_page' => 1,
                'orientation' => 'landscape',
                'content_filter' => 'high',
            ]);

        if (!$response->successful()) {
            Log::warning('Unsplash search failed', ['status' => $response->status(), 'query' => $query]);

            return null;
        }

        $photo = $response->json('results.0');
        if (!is_array($photo)) {
            return null;
        }

        return [
            'url' => (string) ($photo['urls']['regular'] ?? ''),
            'thumb_url' => (string) ($photo['urls']['small'] ?? ''),
            'credit_name' => (string) ($photo['user']['name'] ?? ''),
            'credit_url' => (string) ($photo['user']['links']['html'] ?? '').'?utm_source=geoflow&utm_medium=referral',
            'download_location' => (string) ($photo['links']['download_location'] ?? ''),
        ];
    }

    /**
     * Unsplash API 使用条款要求：图片被实际使用时需 ping 一次 download_location 触发下载计数。
     */
    public function trackDownload(string $downloadLocation): void
    {
        $accessKey = $this->accessKey();
        if ($accessKey === '' || trim($downloadLocation) === '') {
            return;
        }

        try {
            Http::withHeaders(['Authorization' => 'Client-ID '.$accessKey])
                ->timeout(10)
                ->get($downloadLocation);
        } catch (\Throwable $e) {
            Log::warning('Unsplash download tracking failed', ['error' => $e->getMessage()]);
        }
    }

    private function accessKey(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY)->first();
        if (!$setting || !$setting->setting_value) {
            return '';
        }

        return $this->apiKeyCrypto->decrypt((string) $setting->setting_value);
    }
}
