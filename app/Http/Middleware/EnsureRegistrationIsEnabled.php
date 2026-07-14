<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            (bool) config('agencyos.registration_enabled', false),
            Response::HTTP_NOT_FOUND,
        );

        return $next($request);
    }
}
