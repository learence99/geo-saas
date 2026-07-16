<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_audits', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('band')->nullable();
            $table->json('checks')->nullable();
            $table->text('error')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_audits');
    }
};
