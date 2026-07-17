<?php

namespace App\Support\OpenAi;

use App\Models\SiteSetting;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI 图片生成（gpt-image-1，即 ChatGPT 里那个新版图片生成能力）。
 *
 * 复用「AI引用检测」模块里保存的 geo_citations_api_key / geo_citations_provider 设置，
 * 只有当前选择的供应商是 openai 时才可用；不单独维护一份 Key。
 */
class OpenAiImageClient
{
    private const SETTING_KEY_PROVIDER = 'geo_citations_provider';

    private const SETTING_KEY_API_KEY = 'geo_citations_api_key';

    private const API_URL = 'https://api.openai.com/v1/images/generations';

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function hasAccessKey(): bool
    {
        return $this->provider() === 'openai' && $this->accessKey() !== '';
    }

    /**
     * @return array{bytes: string, format: string}|null
     */
    public function generate(string $prompt): ?array
    {
        $accessKey = $this->accessKey();
        if (! $this->hasAccessKey() || trim($prompt) === '') {
            return null;
        }

        $response = Http::withToken($accessKey)
            ->timeout(60)
            ->post(self::API_URL, [
                'model' => 'gpt-image-1',
                'prompt' => $prompt,
                'size' => '1536x1024',
                'quality' => 'medium',
                'n' => 1,
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI image generation failed', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        }

        $b64 = $response->json('data.0.b64_json');
        if (! is_string($b64) || $b64 === '') {
            return null;
        }

        $bytes = base64_decode($b64, true);
        if ($bytes === false) {
            return null;
        }

        return [
            'bytes' => $bytes,
            'format' => (string) ($response->json('output_format') ?? 'png'),
        ];
    }

    private function provider(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY_PROVIDER)->first();

        return $setting?->setting_value ? (string) $setting->setting_value : 'openai';
    }

    private function accessKey(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY_API_KEY)->first();
        if (! $setting || ! $setting->setting_value) {
            return '';
        }

        return $this->apiKeyCrypto->decrypt((string) $setting->setting_value);
    }
}
