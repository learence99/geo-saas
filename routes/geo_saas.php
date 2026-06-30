<?php

// GEO SaaS 后台路由（经 GeoSaasServiceProvider 以 admin.auth 鉴权组 + geo_admin 前缀加载）。
// 放置：routes/geo_saas.php  → 实际 URL 形如 /geo_admin/geo-engine，路由名 admin.geo-engine.index
use App\Http\Controllers\Admin\GeoEngineController;
use App\Http\Controllers\Admin\GeoScoreController;
use App\Http\Controllers\Admin\KeywordExpandController;
use App\Http\Controllers\Admin\KeywordWorkbenchController;
use App\Http\Controllers\Admin\RankingTrackerController;
use App\Http\Controllers\Admin\SiteAuditController;
use App\Http\Controllers\Admin\TitleWorkbenchController;
use Illuminate\Support\Facades\Route;

// 关键词库管理(自有功能模块,跑在原生 keywords 表上)
Route::prefix('keyword-workbench')->name('keyword-workbench.')->group(function () {
    Route::get('/', [KeywordWorkbenchController::class, 'index'])->name('index');
    Route::post('generate', [KeywordWorkbenchController::class, 'generate'])->name('generate');
    Route::post('manual', [KeywordWorkbenchController::class, 'storeManual'])->name('manual');
    Route::post('distill', [KeywordWorkbenchController::class, 'distillTitle'])->name('distill');
});

// 标题库管理(自有功能模块,跑在原生 titles 表上)
Route::get('title-workbench', [TitleWorkbenchController::class, 'index'])->name('title-workbench.index');

// 关键词库 · AI 扩词（焊进原生关键词库；入口在原生详情页按钮）
Route::prefix('keyword-libraries/{libraryId}')->name('keyword-libraries.')->group(function () {
    Route::get('ai-expand', [KeywordExpandController::class, 'form'])->name('ai-expand');
    Route::post('ai-expand', [KeywordExpandController::class, 'submit'])->name('ai-expand.submit');
});
// 关键词库 · 用 AI 生成并创建（创建页卡片：一步建库+填词）
Route::post('keyword-libraries/ai-new', [KeywordExpandController::class, 'createWithAi'])->name('keyword-libraries.ai-new');

Route::prefix('geo-engine')->name('geo-engine.')->group(function () {
    Route::get('/', [GeoEngineController::class, 'index'])->name('index');
    Route::post('generate', [GeoEngineController::class, 'generate'])->name('generate');
    Route::post('save-to-library', [GeoEngineController::class, 'saveToLibrary'])->name('save');
});

Route::prefix('geo-score')->name('geo-score.')->group(function () {
    Route::get('/', [GeoScoreController::class, 'index'])->name('index');
    Route::post('run', [GeoScoreController::class, 'run'])->name('run');
});

Route::prefix('ranking-tracker')->name('ranking-tracker.')->group(function () {
    Route::get('/', [RankingTrackerController::class, 'index'])->name('index');
    Route::post('add', [RankingTrackerController::class, 'store'])->name('add');
    Route::post('check', [RankingTrackerController::class, 'check'])->name('check');
});

// 站点体检 / GEO 诊断(纯 PHP 抓取解析,零 AI 成本)
Route::prefix('site-audit')->name('site-audit.')->group(function () {
    Route::get('/', [SiteAuditController::class, 'index'])->name('index');
    Route::post('run', [SiteAuditController::class, 'run'])->name('run');
});
