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
            'url' => $canonicalUrl ?? route('site.category', $category->slug),
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

@php
    $categoryArticleCount = is_object($articles ?? null) && method_exists($articles, 'total') ? $articles->total() : collect($articles ?? [])->count();
@endphp
@section('content')
    <div class="ne-shell ne-layout">
        <section class="ne-feed">
            <div class="ne-page-head ne-category-head">
                <nav class="ne-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('site.home') }}">{{ __('front.nav.home') }}</a>
                    <span>/</span>
                    <span>{{ $category->name }}</span>
                </nav>
                <h1 class="ne-page-title">{{ $category->name }}</h1>
                <p class="ne-page-desc">{{ $categoryArticleCount }} {{ app()->getLocale() === 'en' ? 'articles · updated live' : '篇文章 · 实时更新' }}</p>
            </div>

            <section class="ne-feed-card">
                <div class="ne-section-title">
                    <span class="ne-title-row">{{ $category->name }}</span>
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
