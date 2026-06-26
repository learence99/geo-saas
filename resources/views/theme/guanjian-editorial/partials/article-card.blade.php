@php
    /** @var \App\Models\Article $article */
    $gjPalette = [['#2c4a63','#1b3146'],['#5e3f5e','#3f2a40'],['#7a5836','#4f3a23'],['#6e3a44','#48262d'],['#356b54','#21402f'],['#2f5d5a','#1d3a38']];
    $gjIdx = $article->category ? (abs(crc32((string) $article->category->name)) % count($gjPalette)) : 0;
    $gjColors = $gjPalette[$gjIdx];
    $gjWm = mb_substr((string) ($article->category->name ?? $article->title), 0, 1);
    $gjSummary = $cardSummaries[$article->id] ?? \Illuminate\Support\Str::limit(strip_tags((string) $article->excerpt), 90);
    $gjPub = $article->published_at ?? $article->created_at;
    $gjAuthor = optional($article->author)->name ?: '编辑部';
    $gjViews = (int) $article->view_count;
    $gjViewsLabel = $gjViews >= 10000 ? round($gjViews / 10000, 1) . '万' : $gjViews;
@endphp
<article class="gj-row">
    <a class="gj-thumb" href="{{ route('site.article', $article->slug) }}" style="--c1:{{ $gjColors[0] }};--c2:{{ $gjColors[1] }}">
        <span class="gj-wm">{{ $gjWm }}</span>
        @if($article->category)<span class="cat">{{ $article->category->name }}</span>@endif
    </a>
    <div class="gj-rbody">
        <h3><a href="{{ route('site.article', $article->slug) }}">{{ $article->title }}</a></h3>
        @if($gjSummary !== '')<p>{{ $gjSummary }}</p>@endif
        <div class="gj-rmeta">
            <span class="gj-aavt sm" style="background:{{ $gjColors[0] }}">{{ mb_substr($gjAuthor, 0, 1) }}</span>
            <span>{{ $gjAuthor }} · {{ optional($gjPub)->format('Y-m-d') }}@if($gjViews) · {{ $gjViewsLabel }} 阅读@endif</span>
        </div>
    </div>
</article>
