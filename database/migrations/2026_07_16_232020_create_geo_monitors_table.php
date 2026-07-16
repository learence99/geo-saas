<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255);
            $table->string('url', 2048)->nullable();
            $table->string('mode', 20)->nullable();
            $table->unsignedTinyInteger('visibility_score')->nullable();
            $table->string('band', 20)->nullable();
            $table->unsignedInteger('total_snapshots')->nullable();
            $table->integer('score_delta')->nullable();
            $table->unsignedTinyInteger('latest_geo_score')->nullable();
            $table->string('latest_geo_band', 20)->nullable();
            $table->json('signals')->nullable();
            $table->json('recommendations')->nullable();
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_monitors');
    }
};
