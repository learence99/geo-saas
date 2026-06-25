@php
    // ===== Beacon 侧栏（Plan A）。复用 GEOFlow 原有 $menu / 路由 / 翻译，功能不变。 =====
    $currentAdmin = auth('admin')->user();
    $adminBrandName = $adminBrandName ?? \App\Support\AdminWeb::siteName();
    $isSuperAdmin = $currentAdmin && method_exists($currentAdmin, 'isSuperAdmin') && $currentAdmin->isSuperAdmin();

    $menu = [
        'dashboard' => ['route' => 'admin.dashboard', 'name' => __('admin.nav.dashboard')],
        'analytics' => ['route' => 'admin.analytics', 'name' => __('admin.nav.analytics')],
        'tasks' => ['route' => 'admin.tasks.index', 'name' => __('admin.nav.tasks')],
        'distribution' => ['route' => 'admin.distribution.index', 'name' => __('admin.nav.distribution')],
        'articles' => ['route' => 'admin.articles.index', 'name' => __('admin.nav.articles')],
        'materials' => ['route' => 'admin.materials.index', 'name' => __('admin.nav.materials')],
        'ai_config' => ['route' => 'admin.ai.configurator', 'name' => __('admin.nav.ai_config')],
        'site_settings' => ['route' => 'admin.site-settings.index', 'name' => __('admin.nav.site_settings')],
    ];
    if ($isSuperAdmin) {
        $menu['admin_users'] = ['route' => 'admin.admin-users.index', 'name' => __('admin.nav.admin_users')];
    }

    $subMap = [
        'admin.analytics' => 'analytics',
        'admin.system-updates.index' => 'dashboard', 'admin.system-updates.check' => 'dashboard',
        'admin.system-updates.plan' => 'dashboard', 'admin.system-updates.backup' => 'dashboard',
        'admin.tasks.create' => 'tasks', 'admin.tasks.edit' => 'tasks',
        'admin.distribution.index' => 'distribution', 'admin.distribution.create' => 'distribution',
        'admin.distribution.store' => 'distribution', 'admin.distribution.edit' => 'distribution',
        'admin.distribution.update' => 'distribution', 'admin.distribution.show' => 'distribution',
        'admin.distribution.jobs' => 'distribution', 'admin.distribution.retry' => 'distribution',
        'admin.distribution.health' => 'distribution', 'admin.distribution.pause' => 'distribution',
        'admin.distribution.activate' => 'distribution', 'admin.distribution.rotate-secret' => 'distribution',
        'admin.articles.create' => 'articles', 'admin.articles.edit' => 'articles',
        'admin.categories.index' => 'materials', 'admin.categories.create' => 'materials', 'admin.categories.edit' => 'materials',
        'admin.authors.index' => 'materials', 'admin.authors.create' => 'materials', 'admin.authors.edit' => 'materials', 'admin.authors.detail' => 'materials',
        'admin.keyword-libraries.index' => 'materials', 'admin.keyword-libraries.create' => 'materials', 'admin.keyword-libraries.edit' => 'materials', 'admin.keyword-libraries.detail' => 'materials',
        'admin.title-libraries.index' => 'materials', 'admin.title-libraries.create' => 'materials', 'admin.title-libraries.edit' => 'materials', 'admin.title-libraries.detail' => 'materials',
        'admin.image-libraries.index' => 'materials', 'admin.image-libraries.create' => 'materials', 'admin.image-libraries.edit' => 'materials', 'admin.image-libraries.detail' => 'materials',
        'admin.knowledge-bases.index' => 'materials', 'admin.knowledge-bases.create' => 'materials', 'admin.knowledge-bases.edit' => 'materials', 'admin.knowledge-bases.detail' => 'materials',
        'admin.url-import' => 'materials',
        'admin.ai-models.index' => 'ai_config', 'admin.ai-prompts' => 'ai_config',
        'admin.site-settings.sensitive-words' => 'site_settings', 'admin.security-settings.index' => 'site_settings',
        'admin.api-tokens.index' => 'admin_users', 'admin.admin-activity-logs' => 'admin_users',
    ];
    $routeName = request()->route()?->getName();
    $resolvedActive = $activeMenu ?? '';
    if ($resolvedActive === '' && $routeName && isset($subMap[$routeName])) {
        $resolvedActive = $subMap[$routeName];
    }

    // Beacon 分组（仅重排展示，不动路由）；标题随语言切换
    $gfuiEn = app()->getLocale() === 'en';
    // GEO SaaS 扩展模块（原生 admin.* 路由，纳入官方 $menu，自动高亮）
    $menu['geo_engine'] = ['route' => 'admin.geo-engine.index', 'name' => $gfuiEn ? 'Topic Engine' : '选词引擎'];
    $menu['ranking_tracker'] = ['route' => 'admin.ranking-tracker.index', 'name' => $gfuiEn ? 'Ranking Tracker' : '排名追踪'];
    $menu['geo_score'] = ['route' => 'admin.geo-score.index', 'name' => $gfuiEn ? 'Content Score' : '内容评分'];
    $groups = [
        ['label' => $gfuiEn ? 'Overview' : '概览', 'items' => ['dashboard', 'analytics']],
        ['label' => $gfuiEn ? 'Content' : '内容', 'items' => ['tasks', 'articles', 'distribution']],
        ['label' => $gfuiEn ? 'GEO Tools' : 'GEO 工具', 'items' => ['geo_engine', 'ranking_tracker', 'geo_score']],
        ['label' => $gfuiEn ? 'Assets' : '素材', 'items' => ['materials']],
        ['label' => $gfuiEn ? 'System' : '系统', 'items' => ['ai_config', 'site_settings', 'admin_users'], 'adv' => true],
    ];
    $icons = [
        'dashboard' => 'layout-grid', 'analytics' => 'bar-chart-3', 'tasks' => 'list-checks',
        'articles' => 'file-text', 'distribution' => 'send', 'materials' => 'folder-open',
        'ai_config' => 'cpu', 'site_settings' => 'settings', 'admin_users' => 'users',
        'geo_engine' => 'sparkles', 'ranking_tracker' => 'target', 'geo_score' => 'gauge',
    ];
    $advItems = ['distribution']; // 简单模式额外隐藏的进阶项
@endphp
<aside class="gfui-sb">
    <a href="{{ route('admin.dashboard') }}" class="gfui-logo">
        <span class="gfui-mark"></span>
        <span class="gfui-name">GEO SAAS<small>{{ $gfuiEn ? 'AI Growth Engine' : 'AI 推荐增长引擎' }}</small></span>
    </a>
    <nav class="gfui-nav">
        @foreach ($groups as $g)
            @php $gitems = array_values(array_filter($g['items'], fn ($k) => isset($menu[$k]))); @endphp
            @if (count($gitems))
                <div class="gfui-group {{ ($g['adv'] ?? false) ? 'gfui-adv' : '' }}">{{ $g['label'] }}</div>
                @foreach ($gitems as $k)
                    <a href="{{ route($menu[$k]['route']) }}"
                       class="gfui-item {{ $resolvedActive === $k ? 'on' : '' }} {{ (($g['adv'] ?? false) || in_array($k, $advItems)) ? 'gfui-adv' : '' }}">
                        <i data-lucide="{{ $icons[$k] ?? 'circle' }}"></i><span>{{ $menu[$k]['name'] }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>
    <div class="gfui-sb-foot">
        <div class="gfui-up">
            <span class="gfui-av">{{ mb_substr($currentAdmin->username ?? 'A', 0, 1) }}</span>
            <div>
                <div class="nm">{{ $currentAdmin->username ?? '' }}</div>
                <div class="rl">{{ $isSuperAdmin ? __('admin.header.super_admin') : __('admin.header.admin') }}</div>
            </div>
        </div>
    </div>
</aside>
