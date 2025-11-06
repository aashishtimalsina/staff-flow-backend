<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilamentSuperAdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // If user is not authenticated or not a superadmin, deny access
        if (!$user || $user->role !== 'superadmin') {
            abort(403, 'Access denied. Superadmin access only.');
        }

        return $next($request);
    }
}
