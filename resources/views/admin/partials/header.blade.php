@php
    $currentAdmin = auth('admin')->user();
    $adminBrandName = $adminBrandName ?? \App\Support\AdminWeb::siteName();
    $isSuperAdmin = $currentAdmin && method_exists($currentAdmin, 'isSuperAdmin') && $currentAdmin->isSuperAdmin();
    $adminRoleLabel = $isSuperAdmin ? __('admin.header.super_admin') : __('admin.header.admin');
    $updateNotification = is_array($adminUpdateNotificationPayload ?? null) ? $adminUpdateNotificationPayload : [];
    $updateState = is_array($updateNotification['state'] ?? null) ? $updateNotification['state'] : [];
    $updateLinks = is_array($updateNotification['links'] ?? null) ? $updateNotification['links'] : [];
    $hasVersionUpdate = !empty($updateState['is_update_available']);
    $isUpdateCenterEnabled = (bool) config('geoflow.update_center_enabled', true);
    $localeForChangelog = app()->getLocale() === 'en' ? 'en' : 'zh-CN';
    $updatePayload = is_array($updateState['payload'] ?? null) ? $updateState['payload'] : [];
    $updateSummary = (string) ($localeForChangelog === 'en'
        ? ($updatePayload['summary_en'] ?? '')
        : ($updatePayload['summary_zh'] ?? ''));
    $changelogLinks = is_array($updateLinks['changelog'] ?? null) ? $updateLinks['changelog'] : [];
    $notificationChangelogUrl = (string) ($changelogLinks[$localeForChangelog] ?? $changelogLinks['zh-CN'] ?? 'https://github.com/yaojingang/GEOFlow/blob/main/docs/CHANGELOG.md');
    $notificationGithubUrl = (string) ($updateLinks['github'] ?? 'https://github.com/yaojingang/GEOFlow');
    $notificationUpdateCenterUrl = $isUpdateCenterEnabled && $isSuperAdmin ? \App\Support\AdminWeb::routePath('admin.system-updates.index') : '';
    $notificationStatus = (string) ($updateState['status'] ?? 'disabled');
    $menu = [
        'dashboard' => ['route' => 'admin.dashboard', 'name' => __('admin.nav.dashboard')],
        'analytics' => ['route' => 'admin.analytics', 'name' => __('admin.nav.analytics')],
        'tasks' => ['route' => 'admin.tasks.index', 'name' => __('admin.nav.tasks')],
        'distribution' => ['route' => 'admin.distribution.index', 'name' => __('admin.nav.distribution')],
        'articles' => ['route' => 'admin.articles.index', 'name' => __('admin.nav.articles')],
        'materials' => ['route' => 'admin.materials.index', 'name' => __('admin.nav.materials')],
        'ai_config' => ['route' => 'admin.ai.configurator', 'name' => __('admin.nav.ai_config')],
        'geo_tools' => ['route' => 'admin.geo-audits.index', 'name' => 'GEO工具'],
        'site_settings' => ['route' => 'admin.site-settings.index', 'name' => __('admin.nav.site_settings')],
    ];
    if ($isSuperAdmin) {
        $menu['admin_users'] = ['route' => 'admin.admin-users.index', 'name' => __('admin.nav.admin_users')];
    }
    $subMap = [
        'admin.analytics' => 'analytics',
        'admin.system-updates.index' => 'dashboard',
        'admin.system-updates.check' => 'dashboard',
        'admin.system-updates.plan' => 'dashboard',
        'admin.system-updates.backup' => 'dashboard',
        'admin.tasks.create' => 'tasks',
        'admin.tasks.edit' => 'tasks',
        'admin.distribution.index' => 'distribution',
        'admin.distribution.create' => 'distribution',
        'admin.distribution.store' => 'distribution',
        'admin.distribution.edit' => 'distribution',
        'admin.distribution.update' => 'distribution',
        'admin.distribution.show' => 'distribution',
        'admin.distribution.jobs' => 'distribution',
        'admin.distribution.retry' => 'distribution',
        'admin.distribution.health' => 'distribution',
        'admin.distribution.pause' => 'distribution',
        'admin.distribution.activate' => 'distribution',
        'admin.distribution.rotate-secret' => 'distribution',
        'admin.articles.create' => 'articles',
        'admin.articles.edit' => 'articles',
        'admin.categories.index' => 'materials',
        'admin.categories.create' => 'materials',
        'admin.categories.edit' => 'materials',
        'admin.authors.index' => 'materials',
        'admin.authors.create' => 'materials',
        'admin.authors.edit' => 'materials',
        'admin.authors.detail' => 'materials',
        'admin.keyword-libraries.index' => 'materials',
        'admin.keyword-libraries.create' => 'materials',
        'admin.keyword-libraries.edit' => 'materials',
        'admin.keyword-libraries.detail' => 'materials',
        'admin.keyword-libraries.detail.update' => 'materials',
        'admin.keyword-libraries.keywords.store' => 'materials',
        'admin.keyword-libraries.keywords.delete' => 'materials',
        'admin.keyword-libraries.import' => 'materials',
        'admin.title-libraries.index' => 'materials',
        'admin.title-libraries.create' => 'materials',
        'admin.title-libraries.edit' => 'materials',
        'admin.title-libraries.detail' => 'materials',
        'admin.title-libraries.titles.store' => 'materials',
        'admin.title-libraries.titles.delete' => 'materials',
        'admin.title-libraries.import' => 'materials',
        'admin.title-libraries.ai-generate' => 'materials',
        'admin.title-libraries.ai-generate.submit' => 'materials',
        'admin.image-libraries.index' => 'materials',
        'admin.image-libraries.create' => 'materials',
        'admin.image-libraries.edit' => 'materials',
        'admin.image-libraries.detail' => 'materials',
        'admin.image-libraries.images.upload' => 'materials',
        'admin.image-libraries.images.delete' => 'materials',
        'admin.image-libraries.detail.update' => 'materials',
        'admin.knowledge-bases.index' => 'materials',
        'admin.knowledge-bases.create' => 'materials',
        'admin.knowledge-bases.edit' => 'materials',
        'admin.knowledge-bases.detail' => 'materials',
        'admin.knowledge-bases.upload' => 'materials',
        'admin.knowledge-bases.detail.update' => 'materials',
        'admin.url-import' => 'materials',
        'admin.ai-models.index' => 'ai_config',
        'admin.ai-prompts' => 'ai_config',
        'admin.geo-audits.index' => 'geo_tools',
        'admin.geo-audits.store' => 'geo_tools',
        'admin.geo-audits.show' => 'geo_tools',
        'admin.geo-audits.delete' => 'geo_tools',
        'admin.geo-citations.index' => 'geo_tools',
        'admin.geo-citations.store' => 'geo_tools',
        'admin.geo-citations.show' => 'geo_tools',
        'admin.geo-citations.settings' => 'geo_tools',
        'admin.geo-citations.delete' => 'geo_tools',
        'admin.geo-fixes.index' => 'geo_tools',
        'admin.geo-fixes.store' => 'geo_tools',
        'admin.geo-fixes.show' => 'geo_tools',
        'admin.geo-fixes.delete' => 'geo_tools',
        'admin.geo-perceptions.index' => 'geo_tools',
        'admin.geo-perceptions.store' => 'geo_tools',
        'admin.geo-perceptions.show' => 'geo_tools',
        'admin.geo-perceptions.delete' => 'geo_tools',
        'admin.geo-monitors.index' => 'geo_tools',
        'admin.geo-monitors.store' => 'geo_tools',
        'admin.geo-monitors.show' => 'geo_tools',
        'admin.geo-monitors.delete' => 'geo_tools',
        'admin.site-settings.sensitive-words' => 'site_settings',
        'admin.site-settings.sensitive-words.store' => 'site_settings',
        'admin.site-settings.sensitive-words.delete' => 'site_settings',
        'admin.security-settings.index' => 'site_settings',
        'admin.security-settings.words.store' => 'site_settings',
        'admin.security-settings.words.delete' => 'site_settings',
        'admin.api-tokens.index' => 'admin_users',
        'admin.api-tokens.store' => 'admin_users',
        'admin.api-tokens.revoke' => 'admin_users',
        'admin.admin-activity-logs' => 'admin_users',
    ];
    $routeName = request()->route()?->getName();
    $resolvedActive = $activeMenu;
    if ($resolvedActive === '' && $routeName && isset($subMap[$routeName])) {
        $resolvedActive = $subMap[$routeName];
    }
@endphp
<aside class="gf-side">
    <div class="gf-brand">
        <div class="gf-logo">{{ mb_substr($adminBrandName, 0, 1) }}</div>
        <div>
            <b>{{ $adminBrandName }}</b>
            <span>{{ __('admin.nav.dashboard') }}</span>
        </div>
    </div>

    <nav class="flex flex-col">
        @foreach ($menu as $key => $item)
            <a href="{{ route($item['route']) }}" class="gf-nav-link @if($resolvedActive === $key) active @endif">
                {{ $item['name'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,.14)">
                <div class="relative mt-2">
                    <button onclick="toggleUserMenu()" class="gf-nav-link w-full" type="button">
                        <div class="w-7 h-7 bg-white/15 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <span class="flex-1 text-left">{{ $currentAdmin->username ?? '' }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </button>

                    <div id="user-menu" class="hidden absolute left-0 bottom-full mb-2 w-56 bg-white rounded-md shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <div class="text-sm text-gray-700">{{ __('admin.header.welcome', ['name' => $currentAdmin->username ?? '']) }}</div>
                            <div class="text-xs text-gray-400">{{ $adminRoleLabel }}</div>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="home" class="w-4 h-4 inline mr-2"></i>
                            {{ __('admin.nav.back_home') }}
                        </a>
                        <a href="{{ route('admin.site-settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="settings" class="w-4 h-4 inline mr-2"></i>
                            {{ __('admin.nav.system_settings') }}
                        </a>
                        @if ($isSuperAdmin)
                            <a href="{{ route('admin.admin-users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-lucide="users" class="w-4 h-4 inline mr-2"></i>
                                {{ __('admin.nav.admin_management') }}
                            </a>
                            <a href="{{ route('admin.admin-activity-logs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-lucide="clipboard-list" class="w-4 h-4 inline mr-2"></i>
                                {{ __('admin.nav.activity_logs') }}
                            </a>
                            <a href="{{ route('admin.api-tokens.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i data-lucide="key-round" class="w-4 h-4 inline mr-2"></i>
                                {{ __('admin.nav.api_tokens') }}
                            </a>
                        @endif
                        <div class="border-t border-gray-100"></div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i>
                                {{ __('admin.button.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
        </div>
</aside>

<style>
    .admin-locale-select {
        background-image: linear-gradient(45deg, transparent 50%, #6b7280 50%), linear-gradient(135deg, #6b7280 50%, transparent 50%);
        background-position: calc(100% - 8px) 52%, calc(100% - 4px) 52%;
        background-size: 4px 4px, 4px 4px;
        background-repeat: no-repeat;
    }
</style>

<script>
    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    document.addEventListener('click', function (event) {
        const userMenu = document.getElementById('user-menu');
        if (userMenu && !event.target.closest('[onclick="toggleUserMenu()"]') && !userMenu.contains(event.target)) {
            userMenu.classList.add('hidden');
        }
    });
</script>
