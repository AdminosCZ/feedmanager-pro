<?php

declare(strict_types=1);

namespace Adminos\Modules\FeedmanagerPro\Http\Middleware;

use Adminos\Modules\FeedmanagerPro\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a Partner from the `{token}` route parameter, verifies HTTP Basic
 * Auth credentials against the partner's stored username/password, and
 * attaches the partner to the request as `feedmanager.partner`. Rejects with:
 *
 *  - 403 on missing/invalid/unknown token, deactivated partner
 *  - 401 + `WWW-Authenticate` on missing or wrong Basic Auth credentials
 *
 * The Basic Auth layer is what makes the URL alone insufficient: even if the
 * URL leaks (via server logs, browser history, etc.), the partner's password
 * is required to actually fetch the feed.
 *
 * @api
 */
final class PartnerTokenAuth
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private const REALM = 'B2B Partner Feed';

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

        if (! $this->basicAuthMatches($request, $partner)) {
            return response()->json(['error' => 'Authentication required.'], 401)
                ->header('WWW-Authenticate', sprintf('Basic realm="%s", charset="UTF-8"', self::REALM));
        }

        $request->attributes->set('feedmanager.partner', $partner);

        return $next($request);
    }

    private function basicAuthMatches(Request $request, Partner $partner): bool
    {
        $expectedUser = (string) $partner->feed_username;
        $expectedPass = (string) $partner->feed_password;

        // Fail closed: a partner without credentials cannot authenticate.
        // Boot/migration logic generates them automatically — this branch only
        // fires if someone manually nulls them out in the database.
        if ($expectedUser === '' || $expectedPass === '') {
            return false;
        }

        $providedUser = (string) $request->getUser();
        $providedPass = (string) $request->getPassword();

        return hash_equals($expectedUser, $providedUser)
            && hash_equals($expectedPass, $providedPass);
    }
}
