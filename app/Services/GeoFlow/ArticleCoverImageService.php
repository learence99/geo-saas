<?php

namespace App\Services\GeoFlow;

use App\Models\Article;
use App\Support\GeoFlow\ImageUrlNormalizer;
use App\Support\OpenAi\OpenAiImageClient;
use App\Support\Unsplash\UnsplashClient;
use Illuminate\Support\Facades\Storage;

/**
 * 文章封面图获取：优先 Unsplash 真实照片，找不到时用 OpenAI gpt-image-1 兜底生成。
 * 手动点击"获取封面图"按钮和自动发布流程共用同一套逻辑，避免重复实现。
 */
class ArticleCoverImageService
{
    /**
     * 中文分类/标题关键词 → Unsplash 更容易匹配到高质量结果的英文检索词。
     * 找不到映射时直接用分类名原文检索（Unsplash 对中文的召回率明显更低，但并非为零）。
     */
    private const KEYWORD_MAP = [
        '旅游' => 'travel',
        '旅行' => 'travel',
        '美国' => 'usa',
        '科技' => 'technology',
        '商业' => 'business',
        '美食' => 'food',
        '健康' => 'health',
        '时尚' => 'fashion',
        '教育' => 'education',
        '金融' => 'finance',
        '生活' => 'lifestyle',
        '房产' => 'real estate',
        '汽车' => 'automotive',
        '体育' => 'sports',
        '母婴' => 'baby family',
        '家居' => 'home interior',
    ];

    public function __construct(
        private readonly UnsplashClient $unsplash,
        private readonly OpenAiImageClient $openAiImage,
    ) {}

    /**
     * @return array{status: string, source: string|null, query: string}
     */
    public function ensureCoverImage(Article $article, bool $force = false): array
    {
        $query = $this->resolveSearchQuery($article);

        if (! $force && (string) ($article->cover_image_url ?? '') !== '') {
            return ['status' => 'skipped_has_cover', 'source' => (string) $article->cover_image_source, 'query' => $query];
        }

        if (! $this->unsplash->hasAccessKey() && ! $this->openAiImage->hasAccessKey()) {
            return ['status' => 'unavailable', 'source' => null, 'query' => $query];
        }

        if ($this->unsplash->hasAccessKey()) {
            $photo = $this->unsplash->searchOne($query);
            if ($photo && $photo['url'] !== '') {
                $article->update([
                    'cover_image_url' => $photo['url'],
                    'cover_image_source' => 'unsplash',
                    'cover_image_credit_name' => $photo['credit_name'],
                    'cover_image_credit_url' => $photo['credit_url'],
                    'cover_image_download_location' => $photo['download_location'],
                ]);
                $this->unsplash->trackDownload($photo['download_location']);

                return ['status' => 'ok', 'source' => 'unsplash', 'query' => $query];
            }
        }

        if ($this->openAiImage->hasAccessKey()) {
            $image = $this->openAiImage->generate($this->buildImagePrompt($article, $query));
            if ($image !== null) {
                $localUrl = $this->storeGeneratedImage($image['bytes'], $image['format']);
                $article->update([
                    'cover_image_url' => $localUrl,
                    'cover_image_source' => 'openai',
                    'cover_image_credit_name' => null,
                    'cover_image_credit_url' => null,
                    'cover_image_download_location' => null,
                ]);

                return ['status' => 'ok', 'source' => 'openai', 'query' => $query];
            }
        }

        return ['status' => 'failed', 'source' => null, 'query' => $query];
    }

    private function storeGeneratedImage(string $bytes, string $format): string
    {
        $extension = $format !== '' ? $format : 'png';
        $directory = 'uploads/ai-covers/'.date('Y/m');
        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $relativePath = $directory.'/'.$filename;
        Storage::disk('public')->put($relativePath, $bytes);

        return ImageUrlNormalizer::toPublicUrl($relativePath);
    }

    private function buildImagePrompt(Article $article, string $query): string
    {
        $title = trim((string) $article->title);

        return "A professional editorial cover illustration about: {$query}"
            .($title !== '' ? ". Related to the article titled \"{$title}\"" : '')
            .'. Photographic or clean illustrative style, no text, no watermark, no logos.';
    }

    private function resolveSearchQuery(Article $article): string
    {
        $categoryName = (string) ($article->category?->name ?? '');
        $terms = [];

        foreach (self::KEYWORD_MAP as $needle => $english) {
            if ($needle !== '' && str_contains($categoryName, $needle)) {
                $terms[] = $english;
            }
        }

        if ($terms !== []) {
            return implode(' ', array_unique($terms));
        }

        return $categoryName !== '' ? $categoryName : 'lifestyle';
    }
}
