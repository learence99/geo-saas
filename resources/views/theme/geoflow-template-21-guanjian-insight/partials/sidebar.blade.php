@php
    $sidebarHotArticles = collect($hotArticles ?? [])->take(6);
    $latestArticles = is_object($articles ?? null) && method_exists($articles, 'getCollection')
        ? $articles->getCollection()->take(6)
        : collect($articles ?? [])->take(6);
    $sidebarArticles = $sidebarHotArticles->isNotEmpty() ? $sidebarHotArticles : $latestArticles;
@endphp
<aside class="ne-sidebar">
    <section class="ne-panel">
        <div class="ne-section-title">
            <span class="ne-title-row">{{ $sidebarHotArticles->isNotEmpty() ? '本周热榜' : __('site.home_latest') }}</span>
        </div>
        <div class="ne-hot-list">
            @forelse($sidebarArticles as $hotArticle)
                <a href="{{ route('site.article', $hotArticle->slug) }}" class="ne-hot-item">
                    <span class="ne-hot-index">{{ $loop->iteration }}</span>
                    <span>{{ $hotArticle->title }}</span>
                </a>
            @empty
                <p style="font-size:13px;color:#A89F8E;">{{ __('site.home_empty_title') }}</p>
            @endforelse
        </div>
    </section>
</aside>
