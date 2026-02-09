<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckEmployee
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isEmployee()) {
            abort(403, 'Доступ только для сотрудников');
        }

        return $next($request);
    }
}