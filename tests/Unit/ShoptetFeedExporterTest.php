<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Unit;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\Feedmanager\Models\Supplier;
use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Adminos\Modules\FeedmanagerPro\Services\ShoptetFeedExporter;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

final class ShoptetFeedExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_products_with_supplier_field(): void
    {
        $extSupplier = Supplier::query()->create([
            'name' => 'Velkoobchod XYZ',
            'slug' => 'vo-xyz',
            'is_own' => false,
            'publish_to_shoptet' => true,
        ]);

        $this->createApprovedProduct($extSupplier, [
            'code' => 'XYZ-001',
            'name' => 'Test Product',
            'price_vat' => '249.50',
        ]);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('<CODE>XYZ-001</CODE>', $result['xml']);
        $this->assertStringContainsString('<SUPPLIER>Velkoobchod XYZ</SUPPLIER>', $result['xml']);
        $this->assertStringContainsString('<NAME>Test Product</NAME>', $result['xml']);
    }

    public function test_excludes_own_eshop_suppliers(): void
    {
        $own = Supplier::query()->create([
            'name' => 'Markstore',
            'slug' => 'markstore',
            'is_own' => true,
        ]);
        $external = Supplier::query()->create([
            'name' => 'Velkoobchod',
            'slug' => 'vo',
            'is_own' => false,
        ]);

        $this->createApprovedProduct($own, ['code' => 'OWN-001', 'name' => 'Own product']);
        $this->createApprovedProduct($external, ['code' => 'EXT-001', 'name' => 'External product']);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('EXT-001', $result['xml']);
        $this->assertStringNotContainsString('OWN-001', $result['xml']);
    }

    public function test_excludes_suppliers_with_publish_to_shoptet_off(): void
    {
        $blocked = Supplier::query()->create([
            'name' => 'Blocked supplier',
            'slug' => 'blocked',
            'is_own' => false,
            'publish_to_shoptet' => false,
        ]);

        $this->createApprovedProduct($blocked, ['code' => 'BLOCK-1', 'name' => 'Blocked']);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(0, $result['count']);
        $this->assertStringNotContainsString('BLOCK-1', $result['xml']);
    }

    public function test_excludes_inactive_suppliers(): void
    {
        $inactive = Supplier::query()->create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'is_own' => false,
            'is_active' => false,
        ]);

        $this->createApprovedProduct($inactive, ['code' => 'INACT-1', 'name' => 'Inactive product']);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(0, $result['count']);
    }

    public function test_excludes_pending_or_rejected_products(): void
    {
        $supplier = $this->externalSupplier();

        $this->createProduct($supplier, ['code' => 'PEND', 'name' => 'Pending', 'status' => Product::STATUS_PENDING]);
        $this->createProduct($supplier, ['code' => 'REJ', 'name' => 'Rejected', 'status' => Product::STATUS_REJECTED]);
        $this->createProduct($supplier, ['code' => 'OK', 'name' => 'Approved', 'status' => Product::STATUS_APPROVED]);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('OK', $result['xml']);
    }

    public function test_excludes_globally_excluded_products(): void
    {
        $supplier = $this->externalSupplier();

        $this->createApprovedProduct($supplier, ['code' => 'EX', 'name' => 'Excluded', 'is_excluded' => true]);
        $this->createApprovedProduct($supplier, ['code' => 'KEEP', 'name' => 'Keep']);

        $config = $this->createConfig();
        $result = $this->exporter()->export($config);

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('KEEP', $result['xml']);
        $this->assertStringNotContainsString('<CODE>EX</CODE>', $result['xml']);
    }

    public function test_full_feed_emits_full_field_set(): void
    {
        $supplier = $this->externalSupplier();

        $this->createApprovedProduct($supplier, [
            'code' => 'FULL-1',
            'name' => 'Full product',
            'description' => '<p>Long description</p>',
            'manufacturer' => 'Acme Corp',
            'price' => '200.00',
            'price_vat' => '242.00',
            'old_price_vat' => '299.00',
            'currency' => 'CZK',
            'ean' => '8590000000001',
            'image_url' => 'https://example.com/img.jpg',
            'category_text' => 'Vehicles',
            'complete_path' => 'Top > Vehicles',
            'stock_quantity' => 50,
            'availability' => 'Skladem',
        ]);

        $result = $this->exporter()->export($this->createConfig(['feed_type' => ShoptetExportConfig::FEED_FULL]));
        $xml = $result['xml'];

        $this->assertStringContainsString('<NAME>Full product</NAME>', $xml);
        $this->assertStringContainsString('<DESCRIPTION>', $xml);
        $this->assertStringContainsString('<MANUFACTURER>Acme Corp</MANUFACTURER>', $xml);
        $this->assertStringContainsString('<SUPPLIER>External Supplier</SUPPLIER>', $xml);
        $this->assertStringContainsString('<CATEGORY>Top &gt; Vehicles</CATEGORY>', $xml);
        $this->assertStringContainsString('<IMAGE>https://example.com/img.jpg</IMAGE>', $xml);
        $this->assertStringContainsString('<CODE>FULL-1</CODE>', $xml);
        $this->assertStringContainsString('<EAN>8590000000001</EAN>', $xml);
        $this->assertStringContainsString('<PRICE>242.00</PRICE>', $xml);
        $this->assertStringContainsString('<STANDARD_PRICE>299.00</STANDARD_PRICE>', $xml);
        $this->assertStringContainsString('<CURRENCY>CZK</CURRENCY>', $xml);
        $this->assertStringContainsString('<AMOUNT>50</AMOUNT>', $xml);
        $this->assertStringContainsString('<AVAILABILITY_IN_STOCK>Skladem</AVAILABILITY_IN_STOCK>', $xml);
        $this->assertStringContainsString('<VISIBLE>1</VISIBLE>', $xml);
    }

    public function test_stock_feed_emits_minimal_field_set(): void
    {
        $supplier = $this->externalSupplier();

        $this->createApprovedProduct($supplier, [
            'code' => 'STOCK-1',
            'name' => 'Stock test',
            'description' => 'Should not appear in stock feed',
            'manufacturer' => 'Acme',
            'price_vat' => '100.00',
            'stock_quantity' => 3,
            'availability' => 'Skladem',
        ]);

        $result = $this->exporter()->export($this->createConfig(['feed_type' => ShoptetExportConfig::FEED_STOCK]));
        $xml = $result['xml'];

        $this->assertStringContainsString('<CODE>STOCK-1</CODE>', $xml);
        $this->assertStringContainsString('<PRICE>100.00</PRICE>', $xml);
        $this->assertStringContainsString('<AMOUNT>3</AMOUNT>', $xml);
        // Stock feed must NOT include the heavy product description fields.
        $this->assertStringNotContainsString('<NAME>', $xml);
        $this->assertStringNotContainsString('<DESCRIPTION>', $xml);
        $this->assertStringNotContainsString('<MANUFACTURER>', $xml);
        $this->assertStringNotContainsString('<SUPPLIER>', $xml);
    }

    public function test_sanitizes_product_codes_for_shoptet_constraints(): void
    {
        $exp = $this->exporter();

        $this->assertSame('ABC-123', $exp->sanitizeCode('abc-123'));
        // iconv translit strips diacritics → ceska, then uppercase → CESKA
        $this->assertSame('CESKA-CODE', $exp->sanitizeCode('česká-code'));
        $this->assertSame('CODE_5', $exp->sanitizeCode('code_5!@#$%'));
        $this->assertLessThanOrEqual(64, mb_strlen($exp->sanitizeCode(str_repeat('A', 100))));
    }

    public function test_emits_placeholder_when_catalogue_is_empty(): void
    {
        $config = $this->createConfig();

        $result = $this->exporter()->export($config);

        // RNG schema requires oneOrMore SHOPITEM. Placeholder keeps the
        // document valid even when there are no eligible products.
        $this->assertSame(0, $result['count']);
        $this->assertStringContainsString('<SHOPITEM>', $result['xml']);
        $this->assertStringContainsString('ADMINOS-PLACEHOLDER', $result['xml']);
    }

    public function test_throws_for_unknown_feed_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $config = ShoptetExportConfig::query()->create([
            'name' => 'Bad',
            'slug' => 'bad',
        ]);
        $config->forceFill(['feed_type' => 'sideways'])->save();

        $this->exporter()->export($config);
    }

    public function test_generated_xml_validates_against_supplier_rng_schema(): void
    {
        if (! extension_loaded('libxml')) {
            $this->markTestSkipped('libxml extension required for RNG validation');
        }

        $supplier = $this->externalSupplier();

        $this->createApprovedProduct($supplier, [
            'code' => 'RNG-1',
            'name' => 'RNG test product',
            'description' => 'Long enough description',
            'manufacturer' => 'Acme',
            'price' => '100.00',
            'price_vat' => '121.00',
            'currency' => 'CZK',
            'ean' => '8590000000001',
            'stock_quantity' => 10,
            'availability' => 'Skladem',
            'image_url' => 'https://example.com/img.jpg',
            'complete_path' => 'Top > Cat',
        ]);

        $config = $this->createConfig(['feed_type' => ShoptetExportConfig::FEED_FULL]);
        $result = $this->exporter()->export($config);

        $this->assertXmlValidatesAgainstRng(
            $result['xml'],
            __DIR__ . '/../fixtures/shoptet-schemas/products-complete-v10.rng',
        );
    }

    public function test_stock_feed_validates_against_supplier_rng_schema(): void
    {
        $supplier = $this->externalSupplier();
        $this->createApprovedProduct($supplier, [
            'code' => 'STK-RNG',
            'name' => 'Stock RNG test',
            'price_vat' => '99.00',
            'stock_quantity' => 5,
            'availability' => 'Skladem',
        ]);

        $config = $this->createConfig(['feed_type' => ShoptetExportConfig::FEED_STOCK]);
        $result = $this->exporter()->export($config);

        $this->assertXmlValidatesAgainstRng(
            $result['xml'],
            __DIR__ . '/../fixtures/shoptet-schemas/products-complete-v10.rng',
        );
    }

    private function exporter(): ShoptetFeedExporter
    {
        return new ShoptetFeedExporter();
    }

    private function externalSupplier(): Supplier
    {
        return Supplier::query()->create([
            'name' => 'External Supplier',
            'slug' => 'external',
            'is_own' => false,
            'publish_to_shoptet' => true,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createConfig(array $overrides = []): ShoptetExportConfig
    {
        return ShoptetExportConfig::query()->create(array_merge([
            'name' => 'Test Shoptet feed',
            'slug' => 'test-feed',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createApprovedProduct(Supplier $supplier, array $attributes): Product
    {
        return $this->createProduct($supplier, array_merge([
            'status' => Product::STATUS_APPROVED,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(Supplier $supplier, array $attributes): Product
    {
        return Product::query()->create(array_merge([
            'supplier_id' => $supplier->id,
            'price_vat' => '100.00',
            'stock_quantity' => 1,
        ], $attributes));
    }

    private function assertXmlValidatesAgainstRng(string $xml, string $schemaPath): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($xml);
        $this->assertTrue($loaded, 'Generated XML must be parseable');

        $valid = $dom->relaxNGValidate($schemaPath);

        if (! $valid) {
            $errors = array_map(fn ($e): string => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $this->fail("Shoptet RNG validation failed:\n" . implode("\n", $errors) . "\n\nXML:\n" . $xml);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertTrue($valid);
    }
}
