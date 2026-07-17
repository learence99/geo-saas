<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('cover_image_url', 1000)->nullable()->after('is_featured');
            $table->string('cover_image_source', 30)->nullable()->after('cover_image_url');
            $table->string('cover_image_credit_name', 150)->nullable()->after('cover_image_source');
            $table->string('cover_image_credit_url', 1000)->nullable()->after('cover_image_credit_name');
            $table->string('cover_image_download_location', 1000)->nullable()->after('cover_image_credit_url');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image_url',
                'cover_image_source',
                'cover_image_credit_name',
                'cover_image_credit_url',
                'cover_image_download_location',
            ]);
        });
    }
};
