<?php

namespace Tests\Feature;

use App\Support\HomepageContentReadiness;
use Database\Seeders\KmdCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageContentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_readiness_reports_missing_visual_assets(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        $summary = app(HomepageContentReadiness::class)->summary();

        $this->assertGreaterThan(0, $summary['issue_count']);
        $this->assertContains('Featured brands', collect($summary['sections'])->pluck('label')->all());
        $this->assertContains(
            'needs_work',
            collect($summary['sections'])->pluck('status')->all(),
        );
    }
}
