@php
    /** @var \App\Models\Article $article */
    $summaryRaw = (string) ($cardSummaries[$article->id] ?? '');
    $summary = trim(preg_replace([
        '/!\[[^\]]*]\([^)]+\)/u',
        '/\[[^\]]+]\([^)]+\)/u',
        '/[`*_>#|~-]+/u',
        '/\s+/u',
    ], [' ', ' ', ' ', ' '], strip_tags($summaryRaw)) ?? '');
    $pub = $article->published_at ?? $article->created_at;
    $categoryName = $article->category?->name ?? __('front.nav.all_articles');
    $initial = mb_substr($categoryName, 0, 1);
    $coverIndex = (int) (($article->category_id ?? $article->id) % 6);
    $authorName = $article->author?->name ?? $categoryName;
    $authorInitial = mb_substr($authorName, 0, 1);
    $relativeTime = $pub?->diffForHumans() ?? '';
    $wordCount = mb_strlen(strip_tags((string) $article->content));
    $readMinutes = max(1, (int) ceil($wordCount / 400));
@endphp
<article class="ne-article-card">
    <a href="{{ route('site.article', $article->slug) }}" class="ne-thumb ne-cover-{{ $coverIndex }}" aria-hidden="true">
        @if($article->cover_image_url)
            <img src="{{ $article->cover_image_url }}" alt="" loading="lazy">
        @else
            {{ $initial }}
        @endif
        <span class="ne-thumb-tag">{{ $categoryName }}</span>
    </a>
    <div>
        <h2 class="ne-article-title">
            <a href="{{ route('site.article', $article->slug) }}">{{ $article->title }}</a>
        </h2>
        @if($summary !== '')
            <p class="ne-article-summary">{{ $summary }}</p>
        @endif
        <div class="ne-card-meta">
            <span class="ne-card-author">
                <span class="ne-avatar">{{ $authorInitial }}</span>
                {{ $authorName }}
            </span>
            @if($relativeTime !== '')
                <span>·</span>
                <time datetime="{{ $pub?->toAtomString() }}">{{ $relativeTime }}</time>
            @endif
            <span>·</span>
            <span>{{ $readMinutes }} {{ __('site.read_minutes') }}</span>
            <span class="ne-card-views">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                {{ number_format((int) $article->view_count) }}
            </span>
        </div>
    </div>
</article>
