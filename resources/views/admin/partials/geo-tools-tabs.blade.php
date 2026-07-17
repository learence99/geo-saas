@php
    $geoTabs = [
        ['route' => 'admin.geo-audits.index', 'pattern' => 'admin.geo-audits.*', 'name' => 'GEO审计'],
        ['route' => 'admin.geo-citations.index', 'pattern' => 'admin.geo-citations.*', 'name' => 'AI引用检测'],
        ['route' => 'admin.geo-fixes.index', 'pattern' => 'admin.geo-fixes.*', 'name' => 'GEO修复'],
        ['route' => 'admin.geo-perceptions.index', 'pattern' => 'admin.geo-perceptions.*', 'name' => 'AI认知快照'],
        ['route' => 'admin.geo-monitors.index', 'pattern' => 'admin.geo-monitors.*', 'name' => 'AI可见度监控'],
        ['route' => 'admin.technical-seo-audits.index', 'pattern' => 'admin.technical-seo-audits.*', 'name' => '技术SEO审计'],
    ];
@endphp
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex flex-wrap gap-6">
        @foreach ($geoTabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium {{ request()->routeIs($tab['pattern']) ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                {{ $tab['name'] }}
            </a>
        @endforeach
    </nav>
</div>
