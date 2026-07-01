<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\KmdCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_listing_returns_published_project_summaries_for_frontend_cards(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        Project::create([
            'title' => 'Hidden Draft Project',
            'slug' => 'hidden-draft-project',
            'overview' => 'This should not be visible.',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/portfolio?per_page=100');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'residential-suite')
            ->assertJsonPath('data.0.is_featured', true)
            ->assertJsonPath('data.0.overview', 'A residential ceiling direction built around warm finishes, clean perimeter lines, and integrated lighting. The reference shows how ceiling materials and lighting details can work together without making the living area feel visually heavy.')
            ->assertJsonMissingPath('data.0.goal')
            ->assertJsonMissing(['slug' => 'hidden-draft-project']);
    }

    public function test_portfolio_detail_returns_case_study_content_and_hides_drafts(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        $draft = Project::create([
            'title' => 'Hidden Draft Project',
            'slug' => 'hidden-draft-project',
            'overview' => 'This should not be visible.',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/portfolio/residential-suite');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'residential-suite')
            ->assertJsonPath('data.scope.0', 'Ceiling layout direction')
            ->assertJsonPath('data.outcomes.0', 'Cleaner ceiling lines')
            ->assertJsonPath('data.process.0.title', 'Read the room')
            ->assertJsonPath('data.services.0.slug', 'ceiling')
            ->assertJsonPath('data.products.0.slug', 'gypsum-board')
            ->assertJsonStructure([
                'data' => [
                    'goal',
                    'challenge',
                    'response',
                    'scope',
                    'outcomes',
                    'process',
                    'services',
                    'products',
                    'gallery',
                    'meta_title',
                    'meta_description',
                    'structured_data',
                ],
            ]);

        $this->getJson("/api/portfolio/{$draft->slug}")->assertNotFound();
    }

    public function test_portfolio_returns_explicit_rich_html_and_plain_text_fields(): void
    {
        Project::create([
            'title' => 'Rich Content Project',
            'slug' => 'rich-content-project',
            'overview' => '<p>Clean <strong>ceiling</strong> direction.</p>',
            'goal' => '<p>Make the room <em>brighter</em>.</p>',
            'challenge' => '<p>Existing lighting was uneven.</p>',
            'response' => '<p>Use layered lighting and simple board lines.</p>',
            'status' => 'published',
        ]);

        $this->getJson('/api/portfolio?per_page=100')
            ->assertOk()
            ->assertJsonPath('data.0.overview', '<p>Clean <strong>ceiling</strong> direction.</p>')
            ->assertJsonPath('data.0.overview_html', '<p>Clean <strong>ceiling</strong> direction.</p>')
            ->assertJsonPath('data.0.overview_text', 'Clean ceiling direction.')
            ->assertJsonMissingPath('data.0.goal_html');

        $this->getJson('/api/portfolio/rich-content-project')
            ->assertOk()
            ->assertJsonPath('data.overview_html', '<p>Clean <strong>ceiling</strong> direction.</p>')
            ->assertJsonPath('data.overview_text', 'Clean ceiling direction.')
            ->assertJsonPath('data.goal_html', '<p>Make the room <em>brighter</em>.</p>')
            ->assertJsonPath('data.goal_text', 'Make the room brighter.')
            ->assertJsonPath('data.challenge_text', 'Existing lighting was uneven.')
            ->assertJsonPath('data.response_text', 'Use layered lighting and simple board lines.');
    }

    public function test_portfolio_featured_filter_uses_explicit_project_flag(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        Project::where('slug', 'workspace-fitout')->update(['is_featured' => false]);

        $response = $this->getJson('/api/portfolio?featured=1&per_page=100');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'residential-suite')
            ->assertJsonMissing(['slug' => 'workspace-fitout']);

        $this->getJson('/api/portfolio?featured=0&per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'workspace-fitout');
    }
}
