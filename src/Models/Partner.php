<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $company_name
 * @property string|null $ico
 * @property string|null $dic
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $street
 * @property string|null $city
 * @property string|null $zip
 * @property string $access_token
 * @property bool $feeds_active
 * @property int $feed_full_limit
 * @property int $feed_stock_limit
 * @property string|null $notes
 *
 * @api
 */
final class Partner extends Model
{
    public const FEED_FULL = 'full';

    public const FEED_STOCK = 'stock';

    /** @var array<int, string> */
    public const FEED_TYPES = [self::FEED_FULL, self::FEED_STOCK];

    protected $table = 'feedmanager_partners';

    protected $guarded = ['id'];

    protected $attributes = [
        'feeds_active' => true,
        'feed_full_limit' => 10,
        'feed_stock_limit' => 50,
        'default_low_stock_threshold' => 5,
        'default_low_stock_availability' => 'Na dotaz',
        'default_out_of_stock_availability' => 'Vyprodáno',
    ];

    protected $casts = [
        'feeds_active' => 'boolean',
        'feed_full_limit' => 'integer',
        'feed_stock_limit' => 'integer',
        'default_low_stock_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $partner): void {
            if (empty($partner->access_token)) {
                $partner->access_token = self::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        return (string) Str::uuid();
    }

    public function regenerateToken(): self
    {
        $this->access_token = self::generateToken();
        $this->save();

        return $this;
    }

    /**
     * @return HasMany<DownloadLog, self>
     */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    public function limitFor(string $feedType): int
    {
        return match ($feedType) {
            self::FEED_FULL => $this->feed_full_limit,
            self::FEED_STOCK => $this->feed_stock_limit,
            default => 0,
        };
    }

    /**
     * Counts the partner's *successful* downloads of the given type in the last
     * 24h. Failed requests (4xx/5xx) don't burn quota — only HTTP 200.
     */
    public function recentSuccessfulDownloadCount(string $feedType): int
    {
        return $this->downloadLogs()
            ->where('feed_type', $feedType)
            ->where('status_code', 200)
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    public function hasReachedLimit(string $feedType): bool
    {
        $limit = $this->limitFor($feedType);

        if ($limit === 0) {
            return true;
        }

        return $this->recentSuccessfulDownloadCount($feedType) >= $limit;
    }

    public function isActive(): bool
    {
        return $this->feeds_active === true;
    }

    public function fullFeedPath(): string
    {
        return "/feed/{$this->access_token}/full";
    }

    public function stockFeedPath(): string
    {
        return "/feed/{$this->access_token}/stock";
    }
}
