<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_perceptions', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048);
            $table->string('mode', 20)->nullable();
            $table->string('brand_name', 150)->nullable();
            $table->string('brand_entity_type', 100)->nullable();
            $table->string('main_topic', 255)->nullable();
            $table->string('detected_audience', 255)->nullable();
            $table->string('citability_grade', 10)->nullable();
            $table->unsignedTinyInteger('trust_score')->nullable();
            $table->text('ai_readable_summary')->nullable();
            $table->json('detected_services')->nullable();
            $table->json('evidence_snippets')->nullable();
            $table->json('supported_claims')->nullable();
            $table->json('unsupported_claims')->nullable();
            $table->json('citation_worthy_facts')->nullable();
            $table->json('ambiguities')->nullable();
            $table->json('missing_authority_signals')->nullable();
            $table->json('schema_types_present')->nullable();
            $table->string('status', 20)->default('running');
            $table->text('error')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_perceptions');
    }
};
