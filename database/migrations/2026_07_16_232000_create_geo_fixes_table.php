<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_fixes', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->string('only_categories', 255)->nullable();
            $table->unsignedTinyInteger('score_before')->nullable();
            $table->unsignedTinyInteger('score_estimated_after')->nullable();
            $table->json('fixes')->nullable();
            $table->json('skipped')->nullable();
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_fixes');
    }
};
