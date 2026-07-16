<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_api_uses_media_library_logo_when_legacy_logo_url_is_empty(): void
    {
        Storage::fake(config('media-library.disk_name'));

        $brand = Brand::create([
            'name' => 'Media Brand',
            'slug' => 'media-brand',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $brand
            ->addMedia(UploadedFile::fake()->image('logo.png'))
            ->toMediaCollection('logo');

        $this->getJson('/api/brands')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'media-brand')
            ->assertJsonPath('data.0.logo_url', $brand->fresh()->getFirstMediaUrl('logo'));
    }

    public function test_service_api_uses_media_library_images_when_legacy_image_url_is_empty(): void
    {
        Storage::fake(config('media-library.disk_name'));

        $service = Service::create([
            'name' => 'Media Service',
            'slug' => 'media-service',
            'short_description' => 'Service with uploaded media.',
            'inquiry_type' => 'quote',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $service
            ->addMedia(UploadedFile::fake()->image('service.jpg'))
            ->toMediaCollection('images');

        $service
            ->addMedia(UploadedFile::fake()->image('portfolio.jpg'))
            ->toMediaCollection('portfolio');

        $service = $service->fresh();

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'media-service')
            ->assertJsonPath('data.0.image_url', $service->getFirstMediaUrl('images'));

        $this->getJson('/api/services/media-service')
            ->assertOk()
            ->assertJsonPath('data.portfolio_images.0', $service->getMedia('portfolio')->first()->getUrl());
    }

    public function test_product_api_returns_uploaded_media_with_a_stable_image_contract(): void
    {
        Storage::fake(config('media-library.disk_name'));

        $category = Category::create([
            'name' => 'Media Products',
            'slug' => 'media-products',
            'type' => 'product',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Uploaded Product',
            'slug' => 'uploaded-product',
            'sku' => 'MEDIA-001',
            'short_description' => 'Product backed by media library uploads.',
            'price' => 10,
            'currency' => 'USD',
            'unit' => 'piece',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $product
            ->addMedia(UploadedFile::fake()->image('product.jpg'))
            ->withCustomProperties(['alt_text' => 'Uploaded product image'])
            ->toMediaCollection('images');

        $url = $product->fresh()->getFirstMediaUrl('images');

        $this->getJson('/api/products/uploaded-product')
            ->assertOk()
            ->assertJsonPath('data.primary_image', $url)
            ->assertJsonPath('data.images.0.image_url', $url)
            ->assertJsonPath('data.images.0.url', $url)
            ->assertJsonPath('data.images.0.is_primary', true);
    }

    public function test_category_api_falls_back_to_uploaded_media(): void
    {
        Storage::fake(config('media-library.disk_name'));

        $category = Category::create([
            'name' => 'Uploaded Category',
            'slug' => 'uploaded-category',
            'type' => 'product',
            'is_active' => true,
        ]);

        $category
            ->addMedia(UploadedFile::fake()->image('category.jpg'))
            ->toMediaCollection('image');

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', $category->fresh()->getFirstMediaUrl('image'));
    }
}
