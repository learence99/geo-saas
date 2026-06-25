<?php

// GEO SaaS 后台路由（经 GeoSaasServiceProvider 以 admin.auth 鉴权组 + geo_admin 前缀加载）。
// 放置：routes/geo_saas.php  → 实际 URL 形如 /geo_admin/geo-engine，路由名 admin.geo-engine.index
use App\Http\Controllers\Admin\GeoEngineController;
use App\Http\Controllers\Admin\GeoScoreController;
use App\Http\Controllers\Admin\KeywordExpandController;
use App\Http\Controllers\Admin\RankingTrackerController;
use Illuminate\Support\Facades\Route;

// 关键词库 · AI 扩词（焊进原生关键词库；入口在原生详情页按钮）
Route::prefix('keyword-libraries/{libraryId}')->name('keyword-libraries.')->group(function () {
    Route::get('ai-expand', [KeywordExpandController::class, 'form'])->name('ai-expand');
    Route::post('ai-expand', [KeywordExpandController::class, 'submit'])->name('ai-expand.submit');
});

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
