<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Feature;

use Adminos\Modules\FeedmanagerPro\Models\DownloadLog;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class B2bFeedEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_route_pattern_doesnt_match(): void
    {
        // Old query-style URL no longer matches the path-based route.
        $this->getJson('/feed')->assertStatus(404);
    }

    public function test_returns_404_when_token_malformed(): void
    {
        // Route constraint `[0-9a-fA-F-]+` rejects non-hex chars before middleware.
        $this->getJson('/feed/not-a-uuid/full')->assertStatus(404);
    }

    public function test_returns_403_when_token_has_right_shape_but_wrong_format(): void
    {
        // Hex chars but not a valid UUID structure — passes route, fails middleware.
        $this->getJson('/feed/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/full')
            ->assertStatus(403);
    }

    public function test_returns_403_when_token_unknown(): void
    {
        $this->getJson('/feed/00000000-0000-0000-0000-000000000000/full')
            ->assertStatus(403);
    }

    public function test_returns_403_when_partner_disabled(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feeds_active' => false,
        ]);

        $this->getJson("/feed/{$partner->access_token}/full")
            ->assertStatus(403);
    }

    public function test_returns_404_when_type_segment_invalid(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        // Route constraint `where('type', 'full|stock')` rejects bogus types
        // before middleware runs.
        $this->getJson("/feed/{$partner->access_token}/bogus")->assertStatus(404);
    }

    public function test_returns_xml_for_valid_full_request(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);
        Product::query()->create([
            'code' => 'SKU-A',
            'name' => 'Demo',
            'price_vat' => '99.9999',
            'is_b2b_allowed' => true,
        ]);

        $response = $this->get("/feed/{$partner->access_token}/full");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertHeader('X-Feedmanager-Type', 'full');
        $response->assertHeader('X-Feedmanager-Count', '1');
        $this->assertStringContainsString('<code>SKU-A</code>', $response->getContent());
    }

    public function test_logs_successful_download(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $this->get("/feed/{$partner->access_token}/stock")->assertStatus(200);

        $log = DownloadLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($partner->id, $log->partner_id);
        $this->assertSame('stock', $log->feed_type);
        $this->assertSame(200, $log->status_code);
        $this->assertSame(0, $log->product_count);
    }

    public function test_returns_429_when_rate_limit_reached(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feed_full_limit' => 1,
        ]);

        $first = $this->get("/feed/{$partner->access_token}/full");
        $first->assertStatus(200);

        $second = $this->get("/feed/{$partner->access_token}/full");
        $second->assertStatus(429);
        $second->assertHeader('Retry-After', '3600');
    }
}
