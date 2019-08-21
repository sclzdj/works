<?php

namespace App\Http\Middleware;

use App\Servers\NavigationServer;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        switch ($guard) {
            case 'admin':
                if (\auth('admin')->id() > 0) {
                    $homeUrl = NavigationServer::homeUrl();
                } else {
                    $homeUrl = '/admin';
                }
                if (!$homeUrl) {
                    $homeUrl = '/admin';
                }
                $path = $homeUrl;
                break;
            default:
                $path = '/home';
                break;
        }
        if (Auth::guard($guard)->check()) {
            return redirect($path);
        }

        return $next($request);
    }
}
