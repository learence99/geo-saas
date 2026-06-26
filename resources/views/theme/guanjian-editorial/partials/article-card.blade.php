@php
    /** @var \App\Models\Article $article */
    $gjPalette = [['#2c4a63','#1b3146'],['#5e3f5e','#3f2a40'],['#7a5836','#4f3a23'],['#6e3a44','#48262d'],['#356b54','#21402f'],['#2f5d5a','#1d3a38']];
    $gjIdx = $article->category ? (abs(crc32((string) $article->category->name)) % count($gjPalette)) : 0;
    $gjColors = $gjPalette[$gjIdx];
    $gjWm = mb_substr((string) ($article->category->name ?? $article->title), 0, 1);
    $gjSummary = \Illuminate\Support\Str::limit($cardSummaries[$article->id] ?? strip_tags((string) $article->excerpt), 78);
    $gjPub = $article->published_at ?? $article->created_at;
    $gjAuthor = optional($article->author)->name ?: '编辑部';
    $gjViews = (int) $article->view_count;
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
            <span>{{ $gjAuthor }} · {{ optional($gjPub)->diffForHumans() }}</span>
        </div>
    </div>
    <div class="gj-rstat">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke="currentColor"><path d="M12 21s-7-4.6-9.5-8.5C.8 9.6 2 6 5.2 6 7 6 8.4 7 12 10 15.6 7 17 6 18.8 6 22 6 23.2 9.6 21.5 12.5 19 16.4 12 21 12 21z"/></svg>
        <span>{{ number_format($gjViews) }}</span>
    </div>
</article>
