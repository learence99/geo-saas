@extends('theme.guanjian-editorial.layout')

@section('content')
@php $gjFmtViews = function ($v) { $v = (int) $v; return $v >= 10000 ? round($v / 10000, 1) . '万' : $v; }; @endphp

<div class="gj-cathead">
    <div class="gj-cathead-top">
        <div>
            <span class="gj-pill" style="background:#2c4a63;color:#fff">分类</span>
            <h1>{{ $category->name }}</h1>
            <div class="sub">{{ $articles->total() }} 篇文章 · 实时更新</div>
            @if(trim((string) $category->description) !== '')<p>{{ $category->description }}</p>@endif
        </div>
        <div class="gj-filter">
            <a class="on" href="{{ route('site.category', $category->slug) }}">最新</a>
            <a href="{{ route('site.category', $category->slug) }}?sort=hot">最热</a>
            <a href="{{ route('site.category', $category->slug) }}?sort=featured">精选</a>
        </div>
    </div>
</div>

@if($articles->isEmpty())
    <div class="gj-empty">
        <h3>{{ __('site.home_empty_title') }}</h3>
        <p>{{ __('site.home_empty_desc') }}</p>
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
                <h3><span class="bar"></span>{{ $category->name }} · 热门</h3>
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
