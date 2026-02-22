<?php

namespace ApiCore\Http\Middleware;

use App\Plugins\Jetstream\src\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class InitializeTenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if tenant is already initialized (e.g., by domain/subdomain middleware)
        if (tenant()) {
            return $next($request);
        }

        // For API requests, check X-Tenant header
        if ($request->is('api/*') || $request->header('Country')) {
            $tenantId = $request->header('Country');

            if ($tenantId) {
                // Find tenant by ID
                $tenant = Tenant::find($tenantId);

                if ($tenant) {
                    // Initialize tenancy
                    tenancy()->initialize($tenant);
                }
            }
        }

        return $next($request);
    }
}

