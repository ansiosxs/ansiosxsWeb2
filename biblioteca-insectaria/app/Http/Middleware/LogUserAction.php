<?php

namespace App\Http\Middleware;

use App\Models\UserActionLog;
use Closure;
use Illuminate\Http\Request;

class LogUserAction
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $response = $next($request);
        $user ??= $request->user();

        if (! $user || $user->isAdmin()) {
            return $response;
        }

        $route = $request->route();
        $controller = $route?->getControllerClass();
        $resourceType = $controller ? class_basename($controller) : 'route';
        $resource = collect($route?->parameters() ?? [])
            ->first(fn ($parameter) => is_object($parameter) && isset($parameter->id));
        $details = [
            'route' => $route?->getName(),
            'method' => $request->method(),
            'input_keys' => array_keys($request->except(['password', 'password_confirmation', 'token', '_token', 'current_password'])),
            'query_keys' => array_keys($request->query()),
        ];

        UserActionLog::create([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'action' => $request->method().' '.($route?->getName() ?? $request->path()),
            'resource_type' => $resourceType,
            'resource_id' => $resource?->id,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
