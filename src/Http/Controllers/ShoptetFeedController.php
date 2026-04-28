<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Http\Controllers;

use Adminos\Modules\FeedmanagerPro\Models\ShoptetExportConfig;
use Adminos\Modules\FeedmanagerPro\Services\ShoptetFeedExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public endpoint Shoptet polls for the auto-import XML feed.
 *
 * @api
 */
final class ShoptetFeedController
{
    public function __construct(
        private readonly ShoptetFeedExporter $exporter,
    ) {
    }

    public function show(Request $request, string $token): Response
    {
        $config = ShoptetExportConfig::query()
            ->where('access_token', $token)
            ->where('is_active', true)
            ->first();

        if ($config === null) {
            return response('Not found.', 404, ['Content-Type' => 'text/plain']);
        }

        try {
            $result = $this->exporter->export($config);
        } catch (\Throwable $e) {
            $config->forceFill([
                'last_run_at' => now(),
                'last_status' => ShoptetExportConfig::STATUS_FAILED,
                'last_message' => substr($e->getMessage(), 0, 1000),
            ])->save();

            return response('Feed export failed.', 500, ['Content-Type' => 'text/plain']);
        }

        $config->forceFill([
            'last_run_at' => now(),
            'last_count' => $result['count'],
            'last_status' => ShoptetExportConfig::STATUS_SUCCESS,
            'last_message' => null,
        ])->save();

        return response($result['xml'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
            'X-Feedmanager-Shoptet-Type' => $config->feed_type,
            'X-Feedmanager-Shoptet-Count' => (string) $result['count'],
        ]);
    }
}
