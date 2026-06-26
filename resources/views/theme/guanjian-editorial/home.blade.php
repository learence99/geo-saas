@extends('theme.guanjian-editorial.layout')

@section('content')
@php
    $gjHome = ($search === '' && ! $category && ! $categoryMissing && (int) request('page', 1) === 1);
    $gjPalette = [['#2c4a63','#1b3146'],['#5e3f5e','#3f2a40'],['#7a5836','#4f3a23'],['#6e3a44','#48262d'],['#356b54','#21402f'],['#2f5d5a','#1d3a38']];
    $gjFmtViews = function ($v) { $v = (int) $v; return $v >= 10000 ? round($v / 10000, 1) . '万' : $v; };
    // 头条:优先 featured,否则首页取最新第一篇
    $gjHero = $featuredArticles->isNotEmpty() ? $featuredArticles->first() : ($gjHome ? $articles->first() : null);
    $gjHeroId = optional($gjHero)->id;
    // 本周热榜:优先 hot,否则用当前列表按浏览量兜底
    $gjHot = $hotArticles->isNotEmpty() ? $hotArticles->take(6) : collect($articles->items())->sortByDesc('view_count')->take(6);
@endphp

@if($gjHome && $gjHero)
    @php
        $hIdx = $gjHero->category ? (abs(crc32((string) $gjHero->category->name)) % count($gjPalette)) : 1;
        $hC = $gjPalette[$hIdx];
        $hWm = mb_substr((string) ($gjHero->category->name ?? $gjHero->title), 0, 1);
        $hSum = \Illuminate\Support\Str::limit($cardSummaries[$gjHero->id] ?? strip_tags((string) $gjHero->excerpt), 116);
        $hPub = $gjHero->published_at ?? $gjHero->created_at;
        $hAuthor = optional($gjHero->author)->name ?: '编辑部';
    @endphp
    <section class="gj-hero">
        <a class="gj-cover" href="{{ route('site.article', $gjHero->slug) }}" style="--c1:{{ $hC[0] }};--c2:{{ $hC[1] }}">
            <span class="gj-wm">{{ $hWm }}</span>
            <span class="gj-hpill">编辑头条@if($gjHero->category) · {{ $gjHero->category->name }}@endif</span>
        </a>
        <div class="gj-hbody">
            <h1><a href="{{ route('site.article', $gjHero->slug) }}">{{ $gjHero->title }}</a></h1>
            @if($hSum !== '')<p>{{ $hSum }}</p>@endif
            <div class="gj-author">
                <span class="gj-aavt">{{ mb_substr($hAuthor, 0, 1) }}</span>
                <div><div class="gj-anm">{{ $hAuthor }}</div><div class="gj-arl">{{ optional($hPub)->diffForHumans() }}</div></div>
            </div>
        </div>
    </section>
@endif

@if($gjHome)
    @include('site.partials.homepage-modules', [
        'homepageModules' => $homepageModules ?? [],
        'homepageStyle' => $homepageStyle ?? [],
        'showHomepageModules' => $showHomepageModules ?? false,
        'articles' => $articles,
        'featuredArticles' => $featuredArticles,
        'hotArticles' => $hotArticles,
    ])
@endif

<div class="gj-sech">
    <h2>{{ $search !== '' ? __('site.search_breadcrumb', ['term' => $search]) : ($category ? $category->name : __('site.home_latest')) }}</h2>
</div>

@if($articles->isEmpty())
    <div class="gj-empty">
        <h3>{{ $search !== '' ? __('site.search_empty_title') : __('site.home_empty_title') }}</h3>
        <p>{{ $search !== '' ? __('site.search_empty_desc') : __('site.home_empty_desc') }}</p>
    </div>
@else
    <div class="gj-feed {{ $gjHot->isEmpty() ? 'solo' : '' }}">
        <div class="gj-list">
            @foreach($articles as $article)
                @if($gjHome && $article->id === $gjHeroId)@continue @endif
                @include('theme.guanjian-editorial.partials.article-card', ['article' => $article, 'showFeaturedBadge' => false])
            @endforeach
            @if($articles->hasPages())
                <div class="gj-page">{{ $articles->onEachSide(1)->links() }}</div>
            @endif
        </div>
        @if($gjHot->isNotEmpty())
            <aside class="gj-hot">
                <h3><span class="bar"></span>{{ $hotArticles->isNotEmpty() ? '本周热榜' : '最新推荐' }}</h3>
                <ol class="gj-hl">
                    @foreach($gjHot as $i => $hot)
                        <li>
                            <span class="n">{{ $i + 1 }}</span>
                            <div>
                                <a class="t" href="{{ route('site.article', $hot->slug) }}">{{ $hot->title }}</a>
                                <div class="rd">{{ $gjFmtViews($hot->view_count) }} 阅读</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </aside>
        @endif
    </div>
@endif
@endsection
