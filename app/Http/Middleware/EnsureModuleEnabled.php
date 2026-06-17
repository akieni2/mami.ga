<?php

namespace App\Http\Middleware;

use App\Support\MamiFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! MamiFeatures::moduleEnabled($module)) {
            return response()->json([
                'success' => false,
                'message' => "Module {$module} is not enabled.",
                'code' => 'module_disabled',
            ], 403);
        }

        return $next($request);
    }
}
