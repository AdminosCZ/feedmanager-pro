<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Tests\Unit;

use Adminos\Modules\FeedmanagerPro\Models\DownloadLog;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\FeedmanagerPro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_generated_on_create(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme s.r.o.']);

        $this->assertNotEmpty($partner->access_token);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $partner->access_token,
        );
    }

    public function test_explicit_token_is_kept_on_create(): void
    {
        $token = '11111111-1111-1111-1111-111111111111';
        $partner = Partner::query()->create([
            'company_name' => 'Acme s.r.o.',
            'access_token' => $token,
        ]);

        $this->assertSame($token, $partner->access_token);
    }

    public function test_regenerate_token_changes_the_token(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme s.r.o.']);
        $original = $partner->access_token;

        $partner->regenerateToken();

        $this->assertNotSame($original, $partner->access_token);
        $this->assertNotEmpty($partner->access_token);
    }

    public function test_limit_for_returns_correct_field(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feed_full_limit' => 7,
            'feed_stock_limit' => 33,
        ]);

        $this->assertSame(7, $partner->limitFor(Partner::FEED_FULL));
        $this->assertSame(33, $partner->limitFor(Partner::FEED_STOCK));
        $this->assertSame(0, $partner->limitFor('unknown'));
    }

    public function test_recent_count_excludes_failed_requests(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => Partner::FEED_FULL,
            'status_code' => 200,
            'created_at' => now(),
        ]);
        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => Partner::FEED_FULL,
            'status_code' => 429,
            'created_at' => now(),
        ]);
        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => Partner::FEED_FULL,
            'status_code' => 500,
            'created_at' => now(),
        ]);

        $this->assertSame(1, $partner->recentSuccessfulDownloadCount(Partner::FEED_FULL));
    }

    public function test_recent_count_excludes_records_older_than_24h(): void
    {
        $partner = Partner::query()->create(['company_name' => 'Acme']);

        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => Partner::FEED_FULL,
            'status_code' => 200,
            'created_at' => now()->subDays(2),
        ]);
        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => Partner::FEED_FULL,
            'status_code' => 200,
            'created_at' => now()->subHours(12),
        ]);

        $this->assertSame(1, $partner->recentSuccessfulDownloadCount(Partner::FEED_FULL));
    }

    public function test_has_reached_limit_is_true_at_or_above(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feed_full_limit' => 2,
        ]);

        $this->assertFalse($partner->hasReachedLimit(Partner::FEED_FULL));

        DownloadLog::query()->create([
            'partner_id' => $partner->id, 'feed_type' => Partner::FEED_FULL,
            'status_code' => 200, 'created_at' => now(),
        ]);

        $this->assertFalse($partner->hasReachedLimit(Partner::FEED_FULL));

        DownloadLog::query()->create([
            'partner_id' => $partner->id, 'feed_type' => Partner::FEED_FULL,
            'status_code' => 200, 'created_at' => now(),
        ]);

        $this->assertTrue($partner->hasReachedLimit(Partner::FEED_FULL));
    }

    public function test_has_reached_limit_when_limit_is_zero(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feed_full_limit' => 0,
        ]);

        $this->assertTrue($partner->hasReachedLimit(Partner::FEED_FULL));
    }

    public function test_is_active_reads_feeds_active_flag(): void
    {
        $partner = Partner::query()->create([
            'company_name' => 'Acme',
            'feeds_active' => false,
        ]);

        $this->assertFalse($partner->isActive());

        $partner->update(['feeds_active' => true]);

        $this->assertTrue($partner->isActive());
    }
}
