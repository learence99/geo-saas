@extends('theme.guanjian-editorial.layout')

@section('content')
@php
    $gjPub = $article->published_at ?? $article->created_at;
    $gjAuthor = optional($article->author)->name ?: '编辑部';
    $gjViews = (int) $article->view_count;
    $gjViewsLabel = $gjViews >= 10000 ? round($gjViews / 10000, 1) . '万' : $gjViews;
@endphp
<div class="gj-art">
    <div class="gj-bc">
        <a href="{{ route('site.home') }}">{{ __('front.nav.home') }}</a>
        @if($article->category) / <a href="{{ route('site.category', $article->category->slug) }}">{{ $article->category->name }}</a>@endif
        / {{ \Illuminate\Support\Str::limit($article->title, 24) }}
    </div>

    @if($article->category)
        <span class="gj-pill" style="background:#f0e2cf;color:#a85f1f">{{ $article->category->name }}</span>
    @endif
    <h1>{{ $article->title }}</h1>
    <div class="gj-amate">
        <span class="gj-aavt">{{ mb_substr($gjAuthor, 0, 1) }}</span>
        <div>
            <div class="gj-anm">{{ $gjAuthor }}</div>
            <div class="gj-arl">{{ optional($gjPub)->format('Y-m-d') }}@if($gjViews) · {{ $gjViewsLabel }} 浏览@endif</div>
        </div>
    </div>

    @if($excerptPlain !== '')
        <div class="gj-summary">{{ $excerptPlain }}</div>
    @endif

    <div class="gj-prose">
        {!! $contentHtml !!}
    </div>

    @if(count($tags) > 0)
        <div class="gj-tags">
            @foreach($tags as $tag)<span class="gj-pill">{{ $tag }}</span>@endforeach
        </div>
    @endif

    <div class="gj-cta">
        <div>
            <div class="t">想了解更多行程与报价？</div>
            <div class="s">查最新班期与余位，顾问 1 对 1 帮你定行程</div>
        </div>
        <a class="btn" href="{{ route('site.home') }}">立即咨询</a>
    </div>

    @if($relatedArticles->isNotEmpty())
        <div class="gj-related">
            <h3>相关阅读</h3>
            <ul>
                @foreach($relatedArticles as $related)
                    <li><a href="{{ route('site.article', $related->slug) }}">{{ $related->title }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
