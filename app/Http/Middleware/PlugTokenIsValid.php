<?php

namespace App\Http\Middleware;

use App\Models\Plug;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlugTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /* @var Plug $plug */
        $plug = $request->route('plug');
        $token = $request->route('token');

        if (!$plug->isMyToken($token)) {
            return response(['message' => 'Erro ao realizar validação da tomada'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
