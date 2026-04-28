<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Http\Controllers;

use Adminos\Modules\FeedmanagerPro\Models\DownloadLog;
use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Adminos\Modules\FeedmanagerPro\Services\B2bFeedExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @api
 */
final class B2bFeedController
{
    public function __construct(
        private readonly B2bFeedExporter $exporter,
    ) {
    }

    public function show(Request $request): Response
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('feedmanager.partner');
        $feedType = (string) $request->route('type', '');

        $result = $this->exporter->export($partner, $feedType);

        DownloadLog::query()->create([
            'partner_id' => $partner->id,
            'feed_type' => $feedType,
            'status_code' => 200,
            'product_count' => $result['count'],
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1024),
        ]);

        return response($result['xml'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
            'X-Feedmanager-Type' => $feedType,
            'X-Feedmanager-Count' => (string) $result['count'],
        ]);
    }
}
