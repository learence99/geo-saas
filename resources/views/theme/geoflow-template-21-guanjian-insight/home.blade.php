@extends('theme.geoflow-template-21-guanjian-insight.layout')

@push('head')
    @php
        $schemaAtContext = chr(64).'context';
        $schemaAtType = chr(64).'type';
        $schemaItems = [];
        foreach ((is_object($articles ?? null) && method_exists($articles, 'getCollection') ? $articles->getCollection() : collect($articles ?? []))->take(10) as $schemaArticle) {
            $schemaItems[] = [
                $schemaAtType => 'ListItem',
                'position' => count($schemaItems) + 1,
                'url' => route('site.article', $schemaArticle->slug),
                'name' => $schemaArticle->title,
            ];
        }
        $collectionSchema = [
            $schemaAtContext => 'https://schema.org',
            $schemaAtType => 'CollectionPage',
            'name' => $pageTitle,
            'description' => $pageDescription,
            'url' => $canonicalUrl ?? route('site.home'),
            'mainEntity' => [
                $schemaAtType => 'ItemList',
                'itemListElement' => $schemaItems,
            ],
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
    @include("site.partials.homepage-modules", ["homepageModules" => $homepageModules ?? [], "homepageStyle" => $homepageStyle ?? [], "showHomepageModules" => $showHomepageModules ?? false, "articles" => $articles ?? collect(), "featuredArticles" => $featuredArticles ?? collect(), "hotArticles" => $hotArticles ?? collect()])

@php
    $homeArticles = is_object($articles ?? null) && method_exists($articles, 'getCollection') ? $articles->getCollection() : collect($articles ?? []);
    $isDefaultHome = $search === '' && !$category && !$categoryMissing;
    $leadArticle = $isDefaultHome ? ($featuredArticles->first() ?: $homeArticles->first()) : null;
    $leadSummary = $leadArticle ? trim((string) ($cardSummaries[$leadArticle->id] ?? '')) : '';
@endphp
    <div class="ne-shell ne-layout">
        @if($leadArticle)
            <section class="ne-home-lead">
                <div class="ne-home-lead-main">
                    <h1>
                        <a href="{{ route('site.article', $leadArticle->slug) }}">{{ $leadArticle->title }}</a>
                    </h1>
                    @if($leadSummary !== '')
                        <p>{{ $leadSummary }}</p>
                    @elseif($siteDescription !== '')
                        <p>{{ $siteDescription }}</p>
                    @endif
                    <a href="{{ route('site.article', $leadArticle->slug) }}" class="ne-card-action">{{ __('site.home_read_more') }} <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>
                @php
                    $leadCoverIndex = (int) (($leadArticle->category_id ?? $leadArticle->id) % 6);
                    $leadCategoryName = $leadArticle->category?->name ?? '';
                @endphp
                <div class="ne-home-headlines ne-cover-{{ $leadCoverIndex }}">
                    <span class="ne-hero-tag">{{ __('site.home_featured') }}{{ $leadCategoryName !== '' ? ' · '.$leadCategoryName : '' }}</span>
                </div>
            </section>
        @endif

        <section class="ne-feed">
            @if($search !== '')
                <div class="ne-page-head">
                    <div class="ne-page-kicker">{{ __('site.search_button') }}</div>
                    <h1 class="ne-page-title">{{ __('site.search_breadcrumb', ['term' => $search]) }}</h1>
                    <p class="ne-page-desc">{{ $pageDescription }}</p>
                </div>
            @elseif($categoryMissing)
                <div class="ne-page-head">
                    <div class="ne-page-kicker">{{ __('site.category_not_found') }}</div>
                    <h1 class="ne-page-title">{{ __('site.category_not_found') }}</h1>
                    <p class="ne-page-desc">{{ $pageDescription }}</p>
                </div>
            @endif

            @php
                $featuredListArticles = $featuredArticles->reject(fn ($item) => $leadArticle && $item->id === $leadArticle->id)->take(5);
            @endphp
            @if($featuredListArticles->isNotEmpty() && $search === '' && !$category)
                <section class="ne-feed-card">
                    <div class="ne-section-title">
                        <span class="ne-title-row">{{ __('site.home_featured') }}</span>
                    </div>
                    <div class="ne-feed">
                        @foreach($featuredListArticles as $article)
                            @include('theme.geoflow-template-21-guanjian-insight.partials.article-card', ['article' => $article])
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="ne-feed-card">
                <div class="ne-section-title">
                    <span class="ne-title-row">{{ $viewTitle }}</span>
                </div>
                <div class="ne-feed">
                    @forelse($articles as $article)
                        @include('theme.geoflow-template-21-guanjian-insight.partials.article-card', ['article' => $article])
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500">
                            {{ __('site.home_empty_title') }}
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="mt-3">
                {{ $articles->links() }}
            </div>
        </section>

        @include('theme.geoflow-template-21-guanjian-insight.partials.sidebar')
    </div>
@endsection
