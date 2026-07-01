<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Console\Command;

class MigrateDescriptionsToHtml extends Command
{
    protected $signature = 'kmd:migrate-descriptions-to-html';
    protected $description = 'Convert plain-text description fields to HTML for the RichEditor';

    public function handle(): void
    {
        $this->convertProductDescriptions();
        $this->convertServiceDescriptions();
        $this->convertBrandDescriptions();
        $this->convertCategoryDescriptions();
    }

    private function convertProductDescriptions(): void
    {
        Product::query()->each(function (Product $product) {
            $changed = false;

            if ($product->description && !str_starts_with($product->description, '<')) {
                $product->description = $this->plainToHtml($product->description);
                $changed = true;
            }

            if ($product->description_kh && !str_starts_with($product->description_kh, '<')) {
                $product->description_kh = $this->plainToHtml($product->description_kh);
                $changed = true;
            }

            if ($changed) {
                $product->saveQuietly();
                $this->line("  Converted product: {$product->name}");
            }
        });

        $this->info('Product descriptions converted.');
    }

    private function convertServiceDescriptions(): void
    {
        Service::query()->each(function (Service $service) {
            $changed = false;

            if ($service->description && !str_starts_with($service->description, '<')) {
                $service->description = $this->plainToHtml($service->description);
                $changed = true;
            }

            if ($service->description_kh && !str_starts_with($service->description_kh, '<')) {
                $service->description_kh = $this->plainToHtml($service->description_kh);
                $changed = true;
            }

            if ($service->faqs && !str_starts_with($service->faqs, '<')) {
                $service->faqs = $this->plainToHtml($service->faqs);
                $changed = true;
            }

            if ($changed) {
                $service->saveQuietly();
                $this->line("  Converted service: {$service->name}");
            }
        });

        $this->info('Service descriptions converted.');
    }

    private function convertBrandDescriptions(): void
    {
        Brand::query()->each(function (Brand $brand) {
            $changed = false;

            if ($brand->description && !str_starts_with($brand->description, '<')) {
                $brand->description = $this->plainToHtml($brand->description);
                $changed = true;
            }

            if ($brand->description_kh && !str_starts_with($brand->description_kh, '<')) {
                $brand->description_kh = $this->plainToHtml($brand->description_kh);
                $changed = true;
            }

            if ($changed) {
                $brand->saveQuietly();
                $this->line("  Converted brand: {$brand->name}");
            }
        });

        $this->info('Brand descriptions converted.');
    }

    private function convertCategoryDescriptions(): void
    {
        Category::query()->each(function (Category $category) {
            if ($category->description && !str_starts_with($category->description, '<')) {
                $category->description = $this->plainToHtml($category->description);
                $category->saveQuietly();
                $this->line("  Converted category: {$category->name}");
            }
        });

        $this->info('Category descriptions converted.');
    }

    private function plainToHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $paragraphs = preg_split('/\n{2,}/', $escaped);
        $paragraphs = array_map('trim', $paragraphs);
        $paragraphs = array_filter($paragraphs);

        if (empty($paragraphs)) {
            return '';
        }

        return '<p>'.implode("</p>\n<p>", $paragraphs)."</p>\n";
    }
}
