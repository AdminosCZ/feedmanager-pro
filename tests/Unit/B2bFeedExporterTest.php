<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Unit;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\FeedmanagerPro\Services\B2bFeedExporter;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

final class B2bFeedExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_feed_includes_b2b_allowed_non_excluded_products(): void
    {
        Product::query()->create([
            'code' => 'INCLUDED',
            'name' => 'Included',
            'price_vat' => '99.0000',
            'is_b2b_allowed' => true,
            'is_excluded' => false,
        ]);
        Product::query()->create([
            'code' => 'EXCLUDED',
            'name' => 'Hidden',
            'price_vat' => '50.0000',
            'is_b2b_allowed' => false,
            'is_excluded' => false,
        ]);
        Product::query()->create([
            'code' => 'GLOBALLY_EXCLUDED',
            'name' => 'Hidden too',
            'price_vat' => '70.0000',
            'is_b2b_allowed' => true,
            'is_excluded' => true,
        ]);

        $partner = Partner::query()->create(['company_name' => 'Acme']);
        $result = $this->exporter()->export($partner, Partner::FEED_FULL);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('<code>INCLUDED</code>', $result['xml']);
        $this->assertStringNotContainsString('EXCLUDED', $result['xml']);
        $this->assertStringNotContainsString('GLOBALLY_EXCLUDED', $result['xml']);
    }

    public function test_full_feed_emits_full_field_set(): void
    {
        Product::query()->create([
            'code' => 'SKU-1',
            'name' => 'Demo',
            'description' => '<p>HTML body</p>',
            'manufacturer' => 'Acme',
            'price' => '82.6446',
            'price_vat' => '99.9999',
            'old_price_vat' => '129.0000',
            'currency' => 'CZK',
            'ean' => '8590000000001',
            'product_number' => 'PN-1',
            'image_url' => 'https://example.com/a.jpg',
            'category_text' => 'Knihy',
            'complete_path' => 'Hlavní > Knihy',
            'stock_quantity' => 50,
            'availability' => 'skladem',
            'is_b2b_allowed' => true,
        ]);

        $partner = Partner::query()->create(['company_name' => 'Acme']);
        $xml = $this->exporter()->export($partner, Partner::FEED_FULL)['xml'];

        $this->assertStringContainsString('<code>SKU-1</code>', $xml);
        $this->assertStringContainsString('<name>Demo</name>', $xml);
        $this->assertStringContainsString('<![CDATA[<p>HTML body</p>]]>', $xml);
        $this->assertStringContainsString('<manufacturer>Acme</manufacturer>', $xml);
        $this->assertStringContainsString('<price>82.6446</price>', $xml);
        $this->assertStringContainsString('<price_vat>99.9999</price_vat>', $xml);
        $this->assertStringContainsString('<old_price_vat>129.0000</old_price_vat>', $xml);
        $this->assertStringContainsString('<currency>CZK</currency>', $xml);
        $this->assertStringContainsString('<ean>8590000000001</ean>', $xml);
        $this->assertStringContainsString('<product_number>PN-1</product_number>', $xml);
        $this->assertStringContainsString('<image_url>https://example.com/a.jpg</image_url>', $xml);
        $this->assertStringContainsString('<category>Knihy</category>', $xml);
        $this->assertStringContainsString('<category_path>Hlavní &gt; Knihy</category_path>', $xml);
        $this->assertStringContainsString('<stock_quantity>50</stock_quantity>', $xml);
        $this->assertStringContainsString('<availability>skladem</availability>', $xml);
        $this->assertStringContainsString('type="full"', $xml);
        $this->assertStringContainsString('count="1"', $xml);
    }

    public function test_stock_feed_emits_minimal_fields(): void
    {
        Product::query()->create([
            'code' => 'SKU-2',
            'name' => 'Should not appear',
            'description' => 'Either',
            'price' => '50.0000',
            'price_vat' => '60.5000',
            'stock_quantity' => 12,
            'availability' => 'skladem',
            'is_b2b_allowed' => true,
        ]);

        $partner = Partner::query()->create(['company_name' => 'Acme']);
        $xml = $this->exporter()->export($partner, Partner::FEED_STOCK)['xml'];

        $this->assertStringContainsString('<code>SKU-2</code>', $xml);
        $this->assertStringContainsString('<price_vat>60.5000</price_vat>', $xml);
        $this->assertStringContainsString('<stock_quantity>12</stock_quantity>', $xml);
        $this->assertStringContainsString('<availability>skladem</availability>', $xml);
        $this->assertStringContainsString('type="stock"', $xml);

        $this->assertStringNotContainsString('<name>', $xml);
        $this->assertStringNotContainsString('<description>', $xml);
        $this->assertStringNotContainsString('<currency>', $xml);
        $this->assertStringNotContainsString('<image_url>', $xml);
    }

    public function test_full_feed_uses_override_fields_when_present(): void
    {
        Product::query()->create([
            'code' => 'SKU-3',
            'name' => 'Original name',
            'description' => 'Original desc',
            'price_vat' => '99.0000',
            'override_name' => 'Branded name',
            'override_description' => 'Branded description',
            'override_price_vat' => '79.0000',
            'is_b2b_allowed' => true,
        ]);

        $partner = Partner::query()->create(['company_name' => 'Acme']);
        $xml = $this->exporter()->export($partner, Partner::FEED_FULL)['xml'];

        $this->assertStringContainsString('<name>Branded name</name>', $xml);
        $this->assertStringContainsString('<![CDATA[Branded description]]>', $xml);
        $this->assertStringContainsString('<price_vat>79.0000</price_vat>', $xml);
        $this->assertStringNotContainsString('Original name', $xml);
        $this->assertStringNotContainsString('Original desc', $xml);
    }

    public function test_invalid_feed_type_throws(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $this->expectException(InvalidArgumentException::class);
        $this->exporter()->export($partner, 'invalid');
    }

    public function test_empty_catalog_returns_empty_count(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);
        $result = $this->exporter()->export($partner, Partner::FEED_FULL);

        $this->assertSame(0, $result['count']);
        $this->assertStringContainsString('count="0"', $result['xml']);
    }

    private function exporter(): B2bFeedExporter
    {
        return new B2bFeedExporter();
    }
}
