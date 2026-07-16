<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_citations', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 150);
            $table->string('domain', 255);
            $table->string('topic', 255)->nullable();
            $table->string('provider', 30)->nullable();
            $table->string('verdict', 30)->nullable();
            $table->unsignedTinyInteger('queries_run')->default(0);
            $table->unsignedTinyInteger('brand_mention_rate')->nullable()->comment('0-100，品牌被提及的答案占比');
            $table->unsignedTinyInteger('domain_citation_rate')->nullable()->comment('0-100，域名被引用为来源的答案占比');
            $table->json('entries')->nullable();
            $table->json('top_cited_domains')->nullable();
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_citations');
    }
};
