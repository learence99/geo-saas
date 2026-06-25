<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * GEO SaaS 扩展模块服务提供者。放置：app/Providers/GeoSaasServiceProvider.php
 * 把我们的页面以"原生后台页"的方式注册：geo_admin 前缀 + admin.* 命名 + 与官方一致的鉴权中间件。
 * 不改动源 routes/web.php，升级不冲突。
 * 需在 bootstrap/providers.php 注册本类（部署脚本会自动加）。
 */
class GeoSaasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $prefix = trim((string) config('geoflow.admin_base_path', 'geo_admin'), '/');

        Route::middleware(['web', 'admin.locale', 'admin.auth', 'admin.activity'])
            ->prefix($prefix)
            ->name('admin.')
            ->group(base_path('routes/geo_saas.php'));
    }
}
