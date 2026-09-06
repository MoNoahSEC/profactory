<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GzipMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Don't compress responses that are already binary or don't have getContent method
        if (!method_exists($response, 'getContent') || !method_exists($response, 'setContent')) {
            return $response;
        }

        // Check if compression is supported by the client
        if (in_array('gzip', $request->getEncodings()) && function_exists('gzencode')) {
            // Compress the response body
            $content = $response->getContent();
            
            // Only compress if the content is large enough to benefit from it (e.g., > 1024 bytes)
            // and if the response is successful or acceptable
            if (strlen($content) > 1024 && !$response->headers->has('Content-Encoding')) {
                // Check if it's text/html or json
                $contentType = $response->headers->get('Content-Type');
                if ($contentType && (str_contains($contentType, 'text/') || str_contains($contentType, 'application/json'))) {
                    $compressed = gzencode($content, 9);
                    
                    // Set the compressed content
                    $response->setContent($compressed);
                    
                    // Add required headers
                    $response->headers->add([
                        'Content-Encoding' => 'gzip',
                        'Vary' => 'Accept-Encoding',
                        'Content-Length' => strlen($compressed)
                    ]);
                }
            }
        }

        return $response;
    }
}
