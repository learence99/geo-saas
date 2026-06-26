<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 关键词模块「结构化需求」加法扩列。
 * 纯加法:全部 nullable + 默认值,不改原生 schema、不影响原生关键词逻辑。
 * 自有关键词工作台读写这些列;原生页面忽略它们,照常工作。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            if (! Schema::hasColumn('keywords', 'core_word')) {
                $table->string('core_word', 80)->nullable()->index()->comment('核心词(扩词来源)');
            }
            if (! Schema::hasColumn('keywords', 'pack')) {
                $table->string('pack', 40)->nullable()->comment('行业包 slug');
            }
            if (! Schema::hasColumn('keywords', 'intent')) {
                $table->string('intent', 16)->nullable()->comment('意图:信息型/决策型/交易型/风险型/品牌型');
            }
            if (! Schema::hasColumn('keywords', 'stage')) {
                $table->string('stage', 16)->nullable()->comment('阶段:知晓期/决策期/转化期');
            }
            if (! Schema::hasColumn('keywords', 'category')) {
                $table->string('category', 40)->nullable()->comment('主题');
            }
            if (! Schema::hasColumn('keywords', 'value')) {
                $table->string('value', 8)->nullable()->comment('商业价值:低/中/中高/高/很高');
            }
            if (! Schema::hasColumn('keywords', 'source')) {
                $table->string('source', 16)->nullable()->default('manual')->comment('来源:ai/manual/import');
            }
            if (! Schema::hasColumn('keywords', 'status')) {
                $table->string('status', 16)->nullable()->default('待处理')->index()->comment('状态:待处理/已生成标题');
            }
        });
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            foreach (['core_word', 'pack', 'intent', 'stage', 'category', 'value', 'source', 'status'] as $col) {
                if (Schema::hasColumn('keywords', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
