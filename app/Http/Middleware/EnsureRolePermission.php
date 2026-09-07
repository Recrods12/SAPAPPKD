<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class EnsureRolePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user(), 403);
        if ($request->user()->hasRole('super-admin')) {
            return $next($request);
        }
        if (Permission::query()->exists()) {
            abort_unless($request->user()->can($permission), 403);
        }

        return $next($request);
    }
}
