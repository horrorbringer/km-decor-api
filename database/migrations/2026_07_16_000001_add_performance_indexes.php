<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured', 'published_at'], 'products_public_featured_idx');
            $table->index(['stock_qty', 'status'], 'products_stock_status_idx');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['is_active', 'is_featured', 'sort_order'], 'services_public_featured_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index(['status', 'is_featured', 'published_at'], 'projects_public_featured_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['created_at', 'status'], 'orders_dashboard_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropIndex('products_public_featured_idx'));
        Schema::table('products', fn (Blueprint $table) => $table->dropIndex('products_stock_status_idx'));
        Schema::table('services', fn (Blueprint $table) => $table->dropIndex('services_public_featured_idx'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropIndex('projects_public_featured_idx'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex('orders_dashboard_idx'));
    }
};
