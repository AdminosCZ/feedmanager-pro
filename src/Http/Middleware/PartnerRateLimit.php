<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Http\Middleware;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-partner rate limit on the B2B feed. Counts the partner's *successful*
 * (HTTP 200) downloads of the requested feed type in the last 24 hours; if
 * the count is at or above the per-feed limit, rejects with 429 and a
 * `Retry-After: 3600` hint.
 *
 * Failed requests don't burn quota — only successful ones count, so a
 * misconfigured cron on the partner side won't lock the partner out.
 *
 * @api
 */
final class PartnerRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Partner|null $partner */
        $partner = $request->attributes->get('feedmanager.partner');

        if (! $partner instanceof Partner) {
            return response()->json(['error' => 'Partner not resolved.'], 500);
        }

        $feedType = (string) $request->route('type', '');

        if (! in_array($feedType, Partner::FEED_TYPES, true)) {
            return response()->json([
                'error' => sprintf(
                    'Invalid feed type "%s". Use one of: %s.',
                    $feedType,
                    implode(', ', Partner::FEED_TYPES),
                ),
            ], 400);
        }

        if ($partner->hasReachedLimit($feedType)) {
            return response()
                ->json([
                    'error' => 'Rate limit reached. Try again later.',
                    'limit' => $partner->limitFor($feedType),
                ], 429)
                ->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
