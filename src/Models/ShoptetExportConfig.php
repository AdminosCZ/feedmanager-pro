<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $access_token
 * @property string $feed_type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_run_at
 * @property int|null $last_count
 * @property string|null $last_status
 * @property string|null $last_message
 * @property string|null $notes
 *
 * @api
 */
final class ShoptetExportConfig extends Model
{
    public const FEED_FULL = 'full';

    public const FEED_STOCK = 'stock';

    /** @var array<int, string> */
    public const FEED_TYPES = [
        self::FEED_FULL,
        self::FEED_STOCK,
    ];

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $table = 'feedmanager_shoptet_export_configs';

    protected $guarded = ['id'];

    protected $attributes = [
        'feed_type' => self::FEED_FULL,
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'last_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $config): void {
            if (empty($config->access_token)) {
                $config->access_token = self::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function regenerateToken(): self
    {
        $this->access_token = self::generateToken();
        $this->save();

        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}
