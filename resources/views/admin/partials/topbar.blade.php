@php
    // ===== Beacon 顶栏（白标版）。移除原"版本更新/通知"铃铛(暴露 GEOFlow + 作者 GitHub)。 =====
    $currentAdmin = auth('admin')->user();
    $isSuperAdmin = $currentAdmin && method_exists($currentAdmin, 'isSuperAdmin') && $currentAdmin->isSuperAdmin();
    $adminRoleLabel = $isSuperAdmin ? __('admin.header.super_admin') : __('admin.header.admin');
    $gfuiEn = app()->getLocale() === 'en';
@endphp
<header class="gfui-tb">
    <div class="gfui-crumb">{{ $pageTitle ?? '' }}</div>
    <div class="gfui-tb-r">
        <span class="gfui-mode" id="gfui-mode" title="{{ $gfuiEn ? 'Simple / Expert mode' : '简单 / 专家模式' }}">
            <span data-mode="simple">{{ $gfuiEn ? 'Simple' : '简单' }}</span><span data-mode="expert" class="on">{{ $gfuiEn ? 'Expert' : '专家' }}</span>
        </span>

        <div class="hidden md:flex items-center rounded-lg border border-gray-200 bg-white px-2 py-1 shadow-sm">
            <i data-lucide="languages" class="w-4 h-4 text-gray-400 mr-1.5"></i>
            <select class="admin-locale-select appearance-none bg-transparent pr-5 text-sm font-medium text-gray-700 outline-none cursor-pointer"
                aria-label="{{ __('admin.header.language') }}" onchange="if (this.value) window.location.href = this.value">
                @foreach (\App\Support\AdminWeb::supportedLocales() as $localeCode => $localeLabel)
                    <option value="{{ route('admin.locale.switch', ['locale' => $localeCode]) }}" @selected(app()->getLocale() === $localeCode)>{{ $localeLabel }}</option>
                @endforeach
            </select>
        </div>

        <div class="relative">
            <button onclick="toggleUserMenu()" class="flex items-center space-x-1 text-sm text-gray-600 hover:text-gray-900 transition-colors duration-200" type="button">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center"><i data-lucide="user" class="w-4 h-4 text-blue-600"></i></div>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50">
                <div class="px-4 py-2 border-b border-gray-100">
                    <div class="text-sm text-gray-700">{{ __('admin.header.welcome', ['name' => $currentAdmin->username ?? '']) }}</div>
                    <div class="text-xs text-gray-400">{{ $adminRoleLabel }}</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="home" class="w-4 h-4 inline mr-2"></i>{{ __('admin.nav.back_home') }}</a>
                <a href="{{ route('admin.site-settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="settings" class="w-4 h-4 inline mr-2"></i>{{ __('admin.nav.system_settings') }}</a>
                @if ($isSuperAdmin)
                    <a href="{{ route('admin.admin-users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="users" class="w-4 h-4 inline mr-2"></i>{{ __('admin.nav.admin_management') }}</a>
                    <a href="{{ route('admin.admin-activity-logs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="clipboard-list" class="w-4 h-4 inline mr-2"></i>{{ __('admin.nav.activity_logs') }}</a>
                    <a href="{{ route('admin.api-tokens.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="key-round" class="w-4 h-4 inline mr-2"></i>{{ __('admin.nav.api_tokens') }}</a>
                @endif
                <div class="border-t border-gray-100"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i>{{ __('admin.button.logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</header>

<style>
    .admin-locale-select{background-image:linear-gradient(45deg,transparent 50%,#6b7280 50%),linear-gradient(135deg,#6b7280 50%,transparent 50%);background-position:calc(100% - 8px) 52%,calc(100% - 4px) 52%;background-size:4px 4px,4px 4px;background-repeat:no-repeat;}
</style>
<script>
    function toggleUserMenu(){var m=document.getElementById('user-menu');if(m)m.classList.toggle('hidden');}
    document.addEventListener('click',function(event){
        var userMenu=document.getElementById('user-menu');
        if(userMenu&&!event.target.closest('[onclick="toggleUserMenu()"]')&&!userMenu.contains(event.target))userMenu.classList.add('hidden');
    });
    (function(){
        var saved=localStorage.getItem('gfui-mode')||'expert';
        function apply(m){
            document.body.classList.toggle('gfui-simple',m==='simple');
            document.querySelectorAll('#gfui-mode span').forEach(function(s){s.classList.toggle('on',s.dataset.mode===m);});
        }
        apply(saved);
        var box=document.getElementById('gfui-mode');
        if(box)box.addEventListener('click',function(e){
            var t=e.target.closest('span[data-mode]');if(!t)return;
            var m=t.dataset.mode;localStorage.setItem('gfui-mode',m);apply(m);
            if(window.lucide)lucide.createIcons();
        });
    })();
</script>
