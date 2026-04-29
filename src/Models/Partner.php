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
 * @property string $tier
 * @property string $access_token
 * @property string|null $feed_username
 * @property string|null $feed_password
 * @property bool $feeds_active
 * @property int $feed_full_limit
 * @property int $feed_stock_limit
 * @property int $default_low_stock_threshold
 * @property string $default_low_stock_availability
 * @property string $default_out_of_stock_availability
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

    public const TIER_STANDARD = 'standard';

    public const TIER_VIP = 'vip';

    /** @var array<int, string> */
    public const TIERS = [self::TIER_STANDARD, self::TIER_VIP];

    /**
     * Conventional low-stock threshold per tier — used when previewing
     * partner-visible availability in admin (e.g. the products list "Pro
     * partnery" tab) without picking one specific partner. Per-partner
     * `default_low_stock_threshold` overrides this; per-product
     * `b2b_low_stock_threshold` raises but never lowers the effective floor.
     *
     * @var array<string, int>
     */
    public const TIER_DEFAULT_THRESHOLDS = [
        self::TIER_STANDARD => 5,
        self::TIER_VIP => 2,
    ];

    protected $table = 'feedmanager_partners';

    protected $guarded = ['id'];

    protected $attributes = [
        'tier' => self::TIER_STANDARD,
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
        // APP_KEY-encrypted (AES-256-CBC + HMAC). Column is TEXT — ciphertext
        // is ~4–5× longer than plaintext.
        'feed_password' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $partner): void {
            if (empty($partner->access_token)) {
                $partner->access_token = self::generateToken();
            }
            if (empty($partner->feed_username)) {
                $partner->feed_username = self::generateUsername();
            }
            if (empty($partner->feed_password)) {
                $partner->feed_password = self::generatePassword();
            }
        });
    }

    public static function generateToken(): string
    {
        return (string) Str::uuid();
    }

    public static function generateUsername(): string
    {
        return 'partner-'.Str::lower(Str::random(8));
    }

    public static function generatePassword(): string
    {
        return Str::random(24);
    }

    public function regenerateToken(): self
    {
        $this->access_token = self::generateToken();
        $this->save();

        return $this;
    }

    /**
     * Rotate everything a partner needs to authenticate at once. Used as the
     * "lost the credentials, send a fresh set" panic button.
     */
    public function regenerateCredentials(): self
    {
        $this->access_token = self::generateToken();
        $this->feed_username = self::generateUsername();
        $this->feed_password = self::generatePassword();
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

    /**
     * Absolute feed URL the admin hands to the partner. In production the
     * scheme is forced to HTTPS — a B2B feed protected by Basic Auth must not
     * be served over plaintext (credentials would travel in clear). In local
     * dev we honour APP_URL so http://localhost still works.
     */
    public function feedUrl(string $type): string
    {
        $url = route('feedmanager.b2b.feed', [
            'token' => $this->access_token,
            'type' => $type,
        ], absolute: true);

        if (app()->isProduction() && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        return $url;
    }

    public function fullFeedUrl(): string
    {
        return $this->feedUrl(self::FEED_FULL);
    }

    public function stockFeedUrl(): string
    {
        return $this->feedUrl(self::FEED_STOCK);
    }
}
