<?php

namespace Tests\Feature;

use App\Models\Brand;
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
}
