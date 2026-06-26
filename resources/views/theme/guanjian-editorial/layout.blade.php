<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('site.partials.seo-head')
    @stack('head')
    {{-- 字体改用系统中文字体（衬线走 宋体/思源宋体），避免国内访问 Google Fonts 卡顿 --}}
    <script src="{{ asset('js/tailwindcss.play-cdn.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/guanjian-editorial/theme.css') }}">
    @if(!empty($headAnalyticsCode))
        {!! $headAnalyticsCode !!}
    @endif
</head>
<body class="gj-body">
    @php $gjActive = $activeNav ?? ''; @endphp
    <div class="gj-hd-wrap">
        <div class="gj-wrap gj-hd">
            <div class="gj-left">
                <a href="{{ route('site.home') }}" class="gj-logo"><span class="cn">{{ $siteName }}</span></a>
                <nav class="gj-nav">
                    <a href="{{ route('site.home') }}" class="{{ $gjActive === 'home' ? 'on' : '' }}">{{ __('front.nav.home') }}</a>
                    @foreach(($navCategories ?? collect())->take(6) as $gjCat)
                        <a href="{{ route('site.category', $gjCat->slug) }}" class="{{ (isset($category) && $category && $category->slug === $gjCat->slug) ? 'on' : '' }}">{{ $gjCat->name }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="gj-right">
                <form class="gj-search" method="get" action="{{ route('site.home') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9a9488" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                    <input type="search" name="search" value="{{ $search ?? '' }}" placeholder="{{ __('site.search_placeholder') }}">
                </form>
                <a href="{{ route('site.home') }}" class="gj-member">立即咨询</a>
                <span class="gj-avt">{{ mb_substr($siteName, 0, 1) }}</span>
            </div>
        </div>
    </div>

    <main class="gj-wrap gj-main">
        @yield('content')
    </main>

    <div class="gj-ft-wrap">
        <div class="gj-wrap gj-ft">
            <div class="l">{{ $siteName }}</div>
            <div class="r">{{ $footerCopyright !== '' ? $footerCopyright : ('© ' . date('Y') . ' ' . $siteName) }}</div>
        </div>
    </div>

    @stack('scripts')
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
