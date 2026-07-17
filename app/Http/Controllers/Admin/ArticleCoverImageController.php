<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\GeoFlow\ArticleCoverImageService;
use Illuminate\Http\RedirectResponse;

class ArticleCoverImageController extends Controller
{
    public function __construct(private readonly ArticleCoverImageService $coverImageService) {}

    public function fetch(int $articleId): RedirectResponse
    {
        $article = Article::findOrFail($articleId);
        $result = $this->coverImageService->ensureCoverImage($article, force: true);

        return match ($result['status']) {
            'ok' => redirect()
                ->route('admin.articles.edit', $article->id)
                ->with('message', $result['source'] === 'unsplash'
                    ? "已从 Unsplash 获取封面图（检索词：{$result['query']}）"
                    : 'Unsplash 未找到合适图片，已改用 OpenAI 生成封面图'),
            'unavailable' => redirect()
                ->route('admin.articles.edit', $article->id)
                ->withErrors(['cover_image' => '尚未配置 Unsplash Access Key 或 OpenAI Key（AI引用检测里配置的供应商需要是 OpenAI）']),
            default => redirect()
                ->route('admin.articles.edit', $article->id)
                ->withErrors(['cover_image' => "未找到匹配图片，OpenAI 生成也失败了（检索词：{$result['query']}）"]),
        };
    }
}
