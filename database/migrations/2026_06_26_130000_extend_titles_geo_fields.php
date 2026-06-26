<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 标题(母标题)模块加法扩列。纯加法,不改原生 schema。
 * `keyword`(来源关键词=血缘)、`keyword_library_id`(标题库←关键词库)原生已有,不动。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            if (! Schema::hasColumn('titles', 'page_type')) {
                $table->string('page_type', 24)->nullable()->comment('页面类型:费用说明页/报名转化页/风险说明页...');
            }
            if (! Schema::hasColumn('titles', 'value')) {
                $table->string('value', 8)->nullable()->comment('商业价值(继承自关键词)');
            }
            if (! Schema::hasColumn('titles', 'priority')) {
                $table->string('priority', 4)->nullable()->comment('优先级 P1/P2/P3');
            }
            if (! Schema::hasColumn('titles', 'status')) {
                $table->string('status', 16)->nullable()->default('未生成')->index()->comment('未生成/已生成/待审核/可发布/已发布');
            }
            if (! Schema::hasColumn('titles', 'source')) {
                $table->string('source', 16)->nullable()->default('manual')->comment('ai/manual');
            }
            if (! Schema::hasColumn('titles', 'pack')) {
                $table->string('pack', 40)->nullable()->comment('行业包');
            }
            if (! Schema::hasColumn('titles', 'core_word')) {
                $table->string('core_word', 80)->nullable()->comment('核心词');
            }
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            foreach (['page_type', 'value', 'priority', 'status', 'source', 'pack', 'core_word'] as $col) {
                if (Schema::hasColumn('titles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
