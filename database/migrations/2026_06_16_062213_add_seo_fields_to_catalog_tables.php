<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_description');
            $table->json('structured_data')->nullable()->after('og_image');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('description_kh');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_description');
            $table->json('structured_data')->nullable()->after('og_image');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('description_kh');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_description');
            $table->json('structured_data')->nullable()->after('og_image');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('description_kh');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_description');
            $table->json('structured_data')->nullable()->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'og_image', 'structured_data']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'og_image', 'structured_data']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'og_image', 'structured_data']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'og_image', 'structured_data']);
        });
    }
};