<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('name_kh')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 20)->default('product')->index();
            $table->string('icon')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('name_kh')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_kh')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_kh')->nullable();
            $table->string('slug')->unique();
            $table->string('sku', 100)->unique();
            $table->string('short_description', 500);
            $table->string('short_description_kh', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_kh')->nullable();
            $table->json('specifications')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('unit', 50);
            $table->unsignedInteger('min_order_qty')->default(1);
            $table->unsignedInteger('stock_qty')->default(0);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('requires_installation')->default(false);
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_new')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_kh')->nullable();
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->string('short_description_kh', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_kh')->nullable();
            $table->string('inquiry_type', 50)->default('quote');
            $table->string('image_url', 500)->nullable();
            $table->json('portfolio_images')->nullable();
            $table->json('faqs')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
