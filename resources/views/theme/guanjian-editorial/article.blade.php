@extends('theme.guanjian-editorial.layout')

@section('content')
@php
    $gjPalette = [['#2c4a63','#1b3146'],['#5e3f5e','#3f2a40'],['#7a5836','#4f3a23'],['#6e3a44','#48262d'],['#356b54','#21402f'],['#2f5d5a','#1d3a38']];
    $gjIdx = $article->category ? (abs(crc32((string) $article->category->name)) % count($gjPalette)) : 0;
    $gjC = $gjPalette[$gjIdx];
    $gjWm = mb_substr((string) ($article->category->name ?? $article->title), 0, 1);
    $gjPub = $article->published_at ?? $article->created_at;
    $gjAuthor = optional($article->author)->name ?: '编辑部';
    $gjViews = (int) $article->view_count;
    $gjRead = max(1, (int) ceil(mb_strlen(strip_tags((string) $contentHtml)) / 380));
    $gjBack = $article->category ? route('site.category', $article->category->slug) : route('site.home');
@endphp
<div class="gj-art">
    <div class="gj-bc">
        <a href="{{ $gjBack }}">‹ 返回</a>
        @if($article->category)<a class="gj-pill" href="{{ route('site.category', $article->category->slug) }}" style="background:#f0e2cf;color:#a85f1f;margin-left:8px">{{ $article->category->name }}</a>@endif
    </div>

    <h1>{{ $article->title }}</h1>

    <div class="gj-amate" style="justify-content:space-between">
        <div style="display:flex;align-items:center;gap:11px">
            <span class="gj-aavt">{{ mb_substr($gjAuthor, 0, 1) }}</span>
            <div>
                <div class="gj-anm">{{ $gjAuthor }}</div>
                <div class="gj-arl">{{ optional($gjPub)->diffForHumans() }} · 约 {{ $gjRead }} 分钟@if($gjViews) · {{ number_format($gjViews) }} 浏览@endif</div>
            </div>
        </div>
        <a class="gj-follow" href="{{ route('site.home') }}">+ 关注</a>
    </div>

    <div class="gj-artcover" style="--c1:{{ $gjC[0] }};--c2:{{ $gjC[1] }}"><span class="gj-wm">{{ $gjWm }}</span></div>

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
