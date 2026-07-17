<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_seo_audits', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('accessibility_score')->nullable();
            $table->unsignedTinyInteger('best_practices_score')->nullable();
            $table->json('core_web_vitals')->nullable();
            $table->json('issues')->nullable();
            $table->string('lighthouse_version', 20)->nullable();
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_seo_audits');
    }
};
