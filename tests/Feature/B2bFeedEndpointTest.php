<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Feature;

use Adminos\Modules\Feedmanager\Models\Product;
use Adminos\Modules\FeedmanagerPro\Models\DownloadLog;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

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
        $this->asPartner($partner)
            ->getJson("/feed/{$partner->access_token}/bogus")->assertStatus(404);
    }

    public function test_returns_401_when_basic_auth_missing(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $response = $this->getJson("/feed/{$partner->access_token}/full");

        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate');
    }

    public function test_returns_401_when_basic_auth_wrong_password(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $response = $this->withBasicAuthHeader($partner->feed_username, 'wrong-password')
            ->getJson("/feed/{$partner->access_token}/full");

        $response->assertStatus(401);
    }

    public function test_returns_401_when_basic_auth_wrong_username(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $response = $this->withBasicAuthHeader('wrong-user', $partner->feed_password)
            ->getJson("/feed/{$partner->access_token}/full");

        $response->assertStatus(401);
    }

    public function test_returns_xml_for_valid_full_request(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);
        // B2B feed čerpá výhradně z vlastního eshopu (is_own=true) — viz
        // poznámka 29 (B2B pipeline). Test musí mít is_own supplier.
        $supplier = \Adminos\Modules\Feedmanager\Models\Supplier::query()->create([
            'name' => 'Vlastní eshop',
            'slug' => 'own-eshop',
            'is_own' => true,
        ]);
        Product::query()->create([
            'supplier_id' => $supplier->id,
            'code' => 'SKU-A',
            'name' => 'Demo',
            'price_vat' => '99.9999',
            'is_b2b_allowed' => true,
        ]);

        $response = $this->asPartner($partner)
            ->get("/feed/{$partner->access_token}/full");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertHeader('X-Feedmanager-Type', 'full');
        $response->assertHeader('X-Feedmanager-Count', '1');
        $this->assertStringContainsString('<code>SKU-A</code>', $response->getContent());
    }

    public function test_logs_successful_download(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        $this->asPartner($partner)
            ->get("/feed/{$partner->access_token}/stock")->assertStatus(200);

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

        $first = $this->asPartner($partner)->get("/feed/{$partner->access_token}/full");
        $first->assertStatus(200);

        $second = $this->asPartner($partner)->get("/feed/{$partner->access_token}/full");
        $second->assertStatus(429);
        $second->assertHeader('Retry-After', '3600');
    }

    private function asPartner(Partner $partner): self
    {
        return $this->withBasicAuthHeader(
            (string) $partner->feed_username,
            (string) $partner->feed_password,
        );
    }

    private function withBasicAuthHeader(string $user, string $pass): self
    {
        $this->withHeader('Authorization', 'Basic '.base64_encode($user.':'.$pass));

        return $this;
    }
}
