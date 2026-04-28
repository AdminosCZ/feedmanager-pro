<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Unit;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\FeedmanagerPro\Services\B2bFeedExporter;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class StockThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_above_threshold_shows_real_count(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'default_low_stock_threshold' => 5,
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 20,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(20, $result['stock_to_emit']);
        $this->assertSame('skladem', $result['availability']);
    }

    public function test_stock_at_threshold_uses_low_stock_label_and_hides_count(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'default_low_stock_threshold' => 5,
            'default_low_stock_availability' => 'Na dotaz',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 5,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(0, $result['stock_to_emit']);
        $this->assertSame('Na dotaz', $result['availability']);
    }

    public function test_stock_zero_uses_out_of_stock_label(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'default_low_stock_threshold' => 5,
            'default_out_of_stock_availability' => 'Vyprodáno',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 0,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(0, $result['stock_to_emit']);
        $this->assertSame('Vyprodáno', $result['availability']);
    }

    public function test_partner_with_zero_threshold_disables_low_stock_logic(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'default_low_stock_threshold' => 0,
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 1,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(1, $result['stock_to_emit']);
        $this->assertSame('skladem', $result['availability']);
    }

    public function test_per_product_threshold_overrides_partner_when_higher(): void
    {
        // VIP partner has threshold 2; product has its own floor 4.
        // Effective = max(2, 4) = 4. Stock 3 falls under, treated as low.
        $partner = Partner::query()->create([
            'company_name' => 'VIP',
            'default_low_stock_threshold' => 2,
            'default_low_stock_availability' => 'Na dotaz',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 3,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
            'b2b_low_stock_threshold' => 4,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(0, $result['stock_to_emit']);
        $this->assertSame('Na dotaz', $result['availability']);
    }

    public function test_per_product_threshold_does_not_lower_partner_threshold(): void
    {
        // Standard partner threshold 5; product has lower floor 1.
        // Effective = max(5, 1) = 5. Stock 3 still falls under partner's 5.
        $partner = Partner::query()->create([
            'company_name' => 'Standard',
            'default_low_stock_threshold' => 5,
            'default_low_stock_availability' => 'Na dotaz',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 3,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
            'b2b_low_stock_threshold' => 1,
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame(0, $result['stock_to_emit']);
        $this->assertSame('Na dotaz', $result['availability']);
    }

    public function test_per_product_low_stock_label_overrides_partner_label(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'default_low_stock_threshold' => 5,
            'default_low_stock_availability' => 'Na dotaz',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 3,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
            'b2b_low_stock_availability' => 'Pouze na objednávku',
        ]);

        $result = (new B2bFeedExporter())->resolveStockVisibility($product, $partner);

        $this->assertSame('Pouze na objednávku', $result['availability']);
    }

    public function test_two_partners_see_different_states_for_same_product(): void
    {
        // Real Markstore use case: standard partner sees "Na dotaz" while
        // VIP partner sees real stock — for the same item, same instant.
        $standard = Partner::query()->create([
            'company_name' => 'Standard',
            'default_low_stock_threshold' => 5,
            'default_low_stock_availability' => 'Na dotaz',
        ]);
        $vip = Partner::query()->create([
            'company_name' => 'VIP',
            'default_low_stock_threshold' => 2,
            'default_low_stock_availability' => 'Na dotaz',
        ]);

        $product = Product::query()->create([
            'code' => 'X', 'name' => 'X',
            'stock_quantity' => 3,
            'availability' => 'skladem',
            'price_vat' => '99',
            'is_b2b_allowed' => true,
        ]);

        $exporter = new B2bFeedExporter();
        $standardView = $exporter->resolveStockVisibility($product, $standard);
        $vipView = $exporter->resolveStockVisibility($product, $vip);

        $this->assertSame(0, $standardView['stock_to_emit']);
        $this->assertSame('Na dotaz', $standardView['availability']);

        $this->assertSame(3, $vipView['stock_to_emit']);
        $this->assertSame('skladem', $vipView['availability']);
    }
}
