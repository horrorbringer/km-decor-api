<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'published_at', 'sort_order'], 'products_catalog_listing_index');
            $table->index(['category_id', 'status'], 'products_category_status_index');
            $table->index(['brand_id', 'status'], 'products_brand_status_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'services_active_sort_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'type', 'sort_order'], 'categories_active_type_sort_index');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'brands_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_catalog_listing_index');
            $table->dropIndex('products_category_status_index');
            $table->dropIndex('products_brand_status_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_active_sort_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_active_type_sort_index');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_active_sort_index');
        });
    }
};
