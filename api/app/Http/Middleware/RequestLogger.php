<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user() ? $request->user()->id : null,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'user_agent' => $request->userAgent(),
        ];

        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $input = $request->all();
            // Mask password
            array_walk_recursive($input, function (&$value, $key) {
                if (in_array($key, ['password', 'password_confirmation', 'token', 'secret'])) {
                    $value = '********';
                }
            });
            $logData['input'] = $input;
        }

        Log::channel('daily')->info('API Request', $logData);

        return $response;
    }
}
