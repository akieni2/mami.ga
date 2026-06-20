<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEconomicOperatorAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessEconomicOperatorAdmin()) {
            abort(403, 'Accès réservé aux administrateurs et superviseurs municipaux.');
        }

        return $next($request);
    }
}
