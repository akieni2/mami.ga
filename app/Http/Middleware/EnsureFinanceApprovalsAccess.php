<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceApprovalsAccess
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $permissions = [
            'municipal.finance.approval.view_queue',
            'municipal.finance.mission.submit',
            'municipal.finance.mission.controller_review',
            'municipal.finance.mission.daf_review',
            'municipal.finance.mission.authorize',
            'municipal.finance.mission.manage',
            'municipal.finance.mission.view',
        ];

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Accès au circuit de validation financière non autorisé.');
    }
}
