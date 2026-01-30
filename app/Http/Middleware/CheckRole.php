<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. You do not have permission to access this resource.'
        ], 403);
    }
}