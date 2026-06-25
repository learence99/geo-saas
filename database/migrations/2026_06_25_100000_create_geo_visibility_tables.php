<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// AI 可见度采集 — 追踪Prompt + 检查快照。放置：database/migrations/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tracked_prompts')) {
            Schema::create('tracked_prompts', function (Blueprint $t) {
                $t->id();
                $t->string('subject', 100);            // 品牌主体
                $t->string('prompt', 500);             // 要追踪的用户问题
                $t->string('engine', 32)->default('deepseek');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('visibility_snapshots')) {
            Schema::create('visibility_snapshots', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tracked_prompt_id')->index();
                $t->string('engine', 32);
                $t->boolean('is_cited')->default(false);
                $t->integer('rank')->nullable();
                $t->text('competitors')->nullable();   // JSON 字符串
                $t->string('sentiment', 16)->nullable();
                $t->text('raw_answer')->nullable();
                $t->timestamp('checked_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visibility_snapshots');
        Schema::dropIfExists('tracked_prompts');
    }
};
