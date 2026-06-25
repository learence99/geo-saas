@php
    $adminBrandName = \App\Support\AdminWeb::siteName();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($pageTitle){{ $pageTitle }} — @endisset{{ $adminBrandName }}</title>
    <script src="{{ asset('js/tailwindcss.play-cdn.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    {{-- Beacon 主题（Plan A）：放在 Tailwind 之后以便覆盖 --}}
    <link rel="stylesheet" href="{{ asset('geoui-beacon.css') }}">
    @stack('styles')
</head>
<body class="bg-gray-50">
<div class="gfui-shell">
    @include('admin.partials.header', [
        'adminBrandName' => $adminBrandName,
        'adminSiteName' => $adminSiteName ?? $adminBrandName,
        'pageTitle' => $pageTitle ?? '',
        'activeMenu' => $activeMenu ?? '',
    ])
    <div class="gfui-col">
        @include('admin.partials.topbar', ['pageTitle' => $pageTitle ?? ''])
        <main class="gfui-content">
            @if (session('message'))
                <div class="admin-flash-alert mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="admin-flash-alert mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </main>
        @include('admin.partials.footer')
    </div>
</div>
@include('admin.partials.welcome-modal')
@vite('resources/js/app.js')
@stack('scripts')
{{-- 重新渲染侧栏新增的 lucide 图标 --}}
<script>document.addEventListener('DOMContentLoaded', function () { if (window.lucide) lucide.createIcons(); });</script>
</body>
</html>
