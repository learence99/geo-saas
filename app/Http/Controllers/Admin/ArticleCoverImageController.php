<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Unsplash\UnsplashClient;
use Illuminate\Http\RedirectResponse;

class ArticleCoverImageController extends Controller
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

    public function __construct(private readonly UnsplashClient $unsplash) {}

    public function fetch(int $articleId): RedirectResponse
    {
        $article = Article::findOrFail($articleId);

        if (!$this->unsplash->hasAccessKey()) {
            return redirect()
                ->route('admin.articles.edit', $article->id)
                ->withErrors(['cover_image' => '尚未配置 Unsplash Access Key']);
        }

        $query = $this->resolveSearchQuery($article);
        $photo = $this->unsplash->searchOne($query);

        if (!$photo || $photo['url'] === '') {
            return redirect()
                ->route('admin.articles.edit', $article->id)
                ->withErrors(['cover_image' => "未找到匹配图片（检索词：{$query}）"]);
        }

        $article->update([
            'cover_image_url' => $photo['url'],
            'cover_image_source' => 'unsplash',
            'cover_image_credit_name' => $photo['credit_name'],
            'cover_image_credit_url' => $photo['credit_url'],
            'cover_image_download_location' => $photo['download_location'],
        ]);

        $this->unsplash->trackDownload($photo['download_location']);

        return redirect()
            ->route('admin.articles.edit', $article->id)
            ->with('message', "已从 Unsplash 获取封面图（检索词：{$query}）");
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
