<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictNgrokAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // If the request comes from Ngrok or Cloudflare Tunnel (Global Internet)
        if (str_contains($host, 'ngrok-free.dev') || str_contains($host, 'trycloudflare.com') || str_contains($host, 'ngrok.app') || str_contains($host, 'ngrok.io')) {
            // Only allow access to the menu and public assets
            $allowedPaths = ['menu', 'build', 'images', 'assets', 'favicon.ico'];
            $path = $request->path();
            
            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if ($path === $allowedPath || str_starts_with($path, $allowedPath . '/')) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                // If they try to access login, dashboard, or anything else, force them to the menu!
                return redirect('/menu');
            }
        }

        return $next($request);
    }
}
