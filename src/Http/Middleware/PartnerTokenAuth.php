<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Http\Middleware;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a Partner from the `{token}` route parameter and attaches it to
 * the request as `feedmanager.partner`. Rejects with 403 on missing/invalid
 * token, deactivated partner, or a token that doesn't match the UUID shape.
 *
 * @api
 */
final class PartnerTokenAuth
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->route('token', '');

        if ($token === '' || preg_match(self::UUID_PATTERN, $token) !== 1) {
            return response()->json(['error' => 'Invalid token.'], 403);
        }

        $partner = Partner::query()->where('access_token', $token)->first();

        if ($partner === null) {
            return response()->json(['error' => 'Unknown token.'], 403);
        }

        if (! $partner->isActive()) {
            return response()->json(['error' => 'Partner feeds are disabled.'], 403);
        }

        $request->attributes->set('feedmanager.partner', $partner);

        return $next($request);
    }
}
