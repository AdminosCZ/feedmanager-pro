<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $partner_id
 * @property string $feed_type
 * @property int $status_code
 * @property int|null $product_count
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @api
 */
final class DownloadLog extends Model
{
    protected $table = 'feedmanager_download_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'partner_id' => 'integer',
        'status_code' => 'integer',
        'product_count' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Partner, self>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
