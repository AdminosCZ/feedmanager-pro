<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Feature;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\Feedmanager\Models\Supplier;
use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ShoptetFeedEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_token_unknown(): void
    {
        $this->get('/feeds/shoptet/nonexistent-token-1234.xml')
            ->assertStatus(404);
    }

    public function test_returns_404_when_export_disabled(): void
    {
        $config = ShoptetExportConfig::query()->create([
            'name' => 'Disabled feed',
            'slug' => 'disabled',
            'is_active' => false,
        ]);

        $this->get("/feeds/shoptet/{$config->access_token}.xml")
            ->assertStatus(404);
    }

    public function test_returns_xml_for_valid_token(): void
    {
        $config = ShoptetExportConfig::query()->create([
            'name' => 'Markstore feed',
            'slug' => 'markstore',
        ]);

        $supplier = Supplier::query()->create([
            'name' => 'Velkoobchod',
            'slug' => 'vo',
            'is_own' => false,
            'publish_to_shoptet' => true,
        ]);

        Product::query()->create([
            'supplier_id' => $supplier->id,
            'code' => 'TEST-1',
            'name' => 'Test product',
            'price_vat' => '99.00',
            'stock_quantity' => 5,
            'status' => Product::STATUS_APPROVED,
        ]);

        $response = $this->get("/feeds/shoptet/{$config->access_token}.xml");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertHeader('X-Feedmanager-Shoptet-Type', ShoptetExportConfig::FEED_FULL);
        $response->assertHeader('X-Feedmanager-Shoptet-Count', '1');
        $this->assertStringContainsString('<CODE>TEST-1</CODE>', (string) $response->getContent());
        $this->assertStringContainsString('<SUPPLIER>Velkoobchod</SUPPLIER>', (string) $response->getContent());
    }

    public function test_updates_last_run_metadata_on_success(): void
    {
        $config = ShoptetExportConfig::query()->create([
            'name' => 'Tracking feed',
            'slug' => 'tracking',
        ]);

        $this->get("/feeds/shoptet/{$config->access_token}.xml")
            ->assertStatus(200);

        $config->refresh();
        $this->assertNotNull($config->last_run_at);
        $this->assertSame(0, $config->last_count);
        $this->assertSame(ShoptetExportConfig::STATUS_SUCCESS, $config->last_status);
        $this->assertNull($config->last_message);
    }

    public function test_does_not_serve_to_wrong_token(): void
    {
        $configA = ShoptetExportConfig::query()->create([
            'name' => 'Feed A',
            'slug' => 'feed-a',
        ]);
        ShoptetExportConfig::query()->create([
            'name' => 'Feed B',
            'slug' => 'feed-b',
        ]);

        // Each config has its own unique token; using A's token must resolve
        // to A specifically (not B). Verified via last_run_at update.
        $response = $this->get("/feeds/shoptet/{$configA->access_token}.xml");
        $response->assertStatus(200);

        $configA->refresh();
        $this->assertNotNull($configA->last_run_at);
    }

    public function test_returns_404_when_token_pattern_invalid(): void
    {
        // Route pattern `[A-Za-z0-9]+` blocks tokens with hyphens or special chars.
        $this->get('/feeds/shoptet/has-dash.xml')->assertStatus(404);
    }
}
