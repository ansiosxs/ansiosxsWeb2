<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountIsConfigured
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()?->needsAccountSetup()) {
            return redirect()->route('account.setup');
        }

        return $next($request);
    }
}
