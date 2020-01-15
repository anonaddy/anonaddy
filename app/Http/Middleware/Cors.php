<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->isCorsRequest($request)) {
            return $next($request);
        }

        if (! in_array($request->getMethod(), ['GET', 'POST', 'OPTIONS'])) {
            return response('Forbidden cors method', 403);
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest();
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Expose-Headers', 'Cache-Control, Content-Language, Content-Type, Expires, Last-Modified, Pragma');

        return $response;
    }

    protected function isCorsRequest($request): bool
    {
        if (! $request->headers->has('Origin')) {
            return false;
        }

        return $request->headers->get('Origin') !== $request->getSchemeAndHttpHost();
    }

    protected function handlePreflightRequest()
    {
        $response = response(null, 204);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', 60 * 60 * 24);

        return $response;
    }
}
