<?php

namespace App\Http\Middleware;

use App\Services\OneDriveService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckOneDriveConnection
{
    protected $oneDriveService;

    public function __construct(OneDriveService $oneDriveService)
    {
        $this->oneDriveService = $oneDriveService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip check for authentication routes
        if ($request->routeIs('onedrive.auth') || $request->routeIs('onedrive.auth.callback')) {
            return $next($request);
        }

        // Check if OneDrive is connected
        if (!$this->oneDriveService->isConnected()) {
            // For API requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'OneDrive is not connected. Please connect OneDrive first.',
                    'action_required' => 'connect_onedrive'
                ], 503);
            }

            // For web requests, redirect to settings with error
            return redirect()->route('settings.show')
                ->with('error', 'OneDrive is not connected. Please connect OneDrive to access this feature.')
                ->with('onedrive_connection_required', true);
        }

        // Log successful connection check for monitoring
        Log::debug('OneDrive connection verified for request', [
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}