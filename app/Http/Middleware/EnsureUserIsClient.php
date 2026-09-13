<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->filled('email')) {
                $email = $request->input('email');
                $user = User::where('email', $email)->first();

                if ($user && $user->role === 'admin') {
                    return redirect()->route('login')
                        ->withErrors(['email' => 'This feature is not available for admin accounts.']);
                }
            }

            return $next($request);
        }

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
