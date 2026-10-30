<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps every page out of search results unless it's explicitly public. Account, admin and
 * auth pages, error pages and non-production environments are never indexed.
 */
class SetRobotsTag
{
    /**
     * The routes search engines may index. These are also the pages listed in the sitemap.
     *
     * @var list<string>
     */
    public const INDEXABLE_ROUTES = [
        'home',
        'job-requests.create',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! self::isIndexable($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * Determine if the request is for a page search engines may index.
     */
    public static function isIndexable(Request $request): bool
    {
        return app()->isProduction()
            && $request->isMethod('GET')
            && in_array($request->route()?->getName(), self::INDEXABLE_ROUTES, true);
    }
}
