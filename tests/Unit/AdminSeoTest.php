<?php

namespace Tests\Unit;

use App\Support\AdminSeo;
use PHPUnit\Framework\TestCase;

class AdminSeoTest extends TestCase
{
    public function test_it_builds_trimmed_plain_text_metadata(): void
    {
        $this->assertSame('A useful product title', AdminSeo::title('<strong>A useful product title</strong>'));
        $this->assertLessThanOrEqual(160, mb_strlen(AdminSeo::description('<p>'.str_repeat('Long description ', 20).'</p>')));
    }

    public function test_it_preserves_custom_structured_data_keys(): void
    {
        $data = AdminSeo::structuredData(
            ['sku' => 'KMD-001', 'custom' => 'keep-me'],
            'Product',
            'Gypsum Board',
            'Interior ceiling and partition board.',
        );

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Product', $data['@type']);
        $this->assertSame('Gypsum Board', $data['name']);
        $this->assertSame('KMD-001', $data['sku']);
        $this->assertSame('keep-me', $data['custom']);
    }
}
