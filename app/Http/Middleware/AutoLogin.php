<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AutoLogin
{
    /**
     * Handle an incoming request and auto-login the default user if unauthenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                    $user = User::whereHas('roles', function ($q) {
                        $q->where('name', 'Admin');
                    })->first() ?? User::first();

                    if (!$user) {
                        $user = User::create([
                            'name' => 'المدير',
                            'email' => 'admin@profactory.local',
                            'password' => bcrypt('password123'),
                            'email_verified_at' => now(),
                        ]);
                    }

                    if (class_exists(\Spatie\Permission\Models\Role::class) && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
                        if (!$user->hasRole('Admin')) {
                            $user->assignRole($role);
                            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                            $user->load('roles', 'permissions');
                        }
                    }

                    if ($user) {
                        Auth::guard('web')->login($user, true);
                        Auth::setUser($user);
                        $request->setUserResolver(fn () => $user);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore errors during bootstrapping
            }
        }

        return $next($request);
    }
}
