<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApartmentApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('apartment.api_token');
        $given = $request->bearerToken();

        if (! $expected || ! is_string($given) || ! hash_equals($expected, $given)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
