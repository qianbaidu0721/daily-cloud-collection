<?php

namespace App\Http\Middleware;

use App\Http\Concerns\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminToken
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $guard = auth('admin')->payload()->get('guard');
        } catch (\Throwable) {
            return $this->error('登录凭证无效，请重新登录', 40102, 401);
        }

        if ($guard !== 'admin') {
            return $this->error('无权访问管理接口', 40302, 403);
        }

        return $next($request);
    }
}
