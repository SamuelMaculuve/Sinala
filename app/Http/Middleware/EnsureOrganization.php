<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user()?->is_super_admin) return redirect()->route('admin.plans.index');
        abort_unless($request->user()?->organization_id && $request->user()->organization,403,'Utilizador sem organização activa.');
        return $next($request);
    }
}
