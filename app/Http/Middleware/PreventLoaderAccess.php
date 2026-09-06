<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventLoaderAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->hasRole('Admin')) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->hasRole('Loader')) {
            if (!$request->routeIs('loading.*') && !$request->routeIs('shipments.*') && !$request->routeIs('profile.*') && !$request->routeIs('logout')) {
                return redirect()->route('loading.index')->with('error', 'غير مصرح لك بالوصول لهذه الصفحة.');
            }
        } elseif ($user->hasRole('Driver')) {
            if (!$request->routeIs('driver.*') && !$request->routeIs('profile.*') && !$request->routeIs('logout')) {
                return redirect()->route('driver.dashboard')->with('error', 'غير مصرح لك بالوصول لهذه الصفحة.');
            }
        } elseif ($user->hasRole('Supervisor')) {
            $allowed = $request->routeIs([
                'supervisor.*',
                'attendance.fast*',
                'attendance.unified*',
                'attendance.production*',
                'production.daily',
                'profile.*',
                'logout',
            ]);

            if (!$allowed) {
                return redirect()->route('supervisor.attendance.index')->with('error', 'غير مصرح لك بالوصول لهذه الصفحة.');
            }
        }

        return $next($request);
    }
}
