@extends('theme.guanjian-editorial.layout')

@section('content')
@php
    $gjHome = ($search === '' && ! $category && ! $categoryMissing && (int) request('page', 1) === 1);
    $gjPalette = [['#2c4a63','#1b3146'],['#5e3f5e','#3f2a40'],['#7a5836','#4f3a23'],['#6e3a44','#48262d'],['#356b54','#21402f'],['#2f5d5a','#1d3a38']];
    $gjFmtViews = function ($v) { $v = (int) $v; return $v >= 10000 ? round($v / 10000, 1) . '万' : $v; };
@endphp

@if($gjHome && $featuredArticles->isNotEmpty())
    @php
        $h = $featuredArticles->first();
        $hIdx = $h->category ? (abs(crc32((string) $h->category->name)) % count($gjPalette)) : 1;
        $hC = $gjPalette[$hIdx];
        $hWm = mb_substr((string) ($h->category->name ?? $h->title), 0, 1);
        $hSum = $cardSummaries[$h->id] ?? \Illuminate\Support\Str::limit(strip_tags((string) $h->excerpt), 120);
        $hPub = $h->published_at ?? $h->created_at;
        $hAuthor = optional($h->author)->name ?: '编辑部';
    @endphp
    <section class="gj-hero">
        <a class="gj-cover" href="{{ route('site.article', $h->slug) }}" style="--c1:{{ $hC[0] }};--c2:{{ $hC[1] }}">
            <span class="gj-wm">{{ $hWm }}</span>
            <span class="gj-hpill">编辑头条@if($h->category) · {{ $h->category->name }}@endif</span>
        </a>
        <div class="gj-hbody">
            <h1><a href="{{ route('site.article', $h->slug) }}">{{ $h->title }}</a></h1>
            @if($hSum !== '')<p>{{ $hSum }}</p>@endif
            <div class="gj-author">
                <span class="gj-aavt">{{ mb_substr($hAuthor, 0, 1) }}</span>
                <div><div class="gj-anm">{{ $hAuthor }}</div><div class="gj-arl">{{ optional($hPub)->diffForHumans() }}</div></div>
            </div>
        </div>
    </section>
@endif

@if($search !== '')
    <div class="gj-cathead"><h1>{{ __('site.search_breadcrumb', ['term' => $search]) }}</h1></div>
@elseif($category)
    <div class="gj-cathead"><span class="gj-pill" style="background:#2c4a63;color:#fff">分类</span><h1>{{ $category->name }}</h1>@if(trim((string) $category->description) !== '')<p>{{ $category->description }}</p>@endif</div>
@elseif($categoryMissing)
    <div class="gj-cathead"><h1>{{ __('site.category_not_found') }}</h1></div>
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
    <div class="gj-feed {{ $hotArticles->isEmpty() ? 'solo' : '' }}">
        <div class="gj-list">
            @foreach($articles as $article)
                @include('theme.guanjian-editorial.partials.article-card', ['article' => $article, 'showFeaturedBadge' => false])
            @endforeach
            @if($articles->hasPages())
                <div class="gj-page">{{ $articles->onEachSide(1)->links() }}</div>
            @endif
        </div>
        @if($hotArticles->isNotEmpty())
            <aside class="gj-hot">
                <h3><span class="bar"></span>本周热榜</h3>
                <ol class="gj-hl">
                    @foreach($hotArticles->take(6) as $i => $hot)
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
