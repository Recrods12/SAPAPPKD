<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireHttpsInProduction
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'production' && ! $request->isSecure()) {
            if (! $request->isMethodSafe()) {
                abort(400, 'Koneksi HTTPS wajib digunakan. Muat ulang halaman melalui alamat HTTPS.');
            }

            return redirect()->secure($request->getRequestUri(), 308);
        }

        return $next($request);
    }
}
