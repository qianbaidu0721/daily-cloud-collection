<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.token' => \App\Http\Middleware\EnsureAdminToken::class,
        ]);

        // API 无 session 登录页，避免未认证时重定向到不存在的 login 路由导致 500
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $unauthorized = fn (string $msg = '未登录或登录已过期') => response()->json([
            'code' => 40101,
            'msg' => $msg,
            'data' => null,
        ], 401);

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($unauthorized) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $unauthorized();
            }
        });

        $exceptions->render(function (TokenExpiredException $e, Request $request) use ($unauthorized) {
            if ($request->is('api/*')) {
                return $unauthorized('登录已过期，请重新登录');
            }
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) use ($unauthorized) {
            if ($request->is('api/*')) {
                return $unauthorized('登录凭证无效，请重新登录');
            }
        });

        $exceptions->render(function (JWTException $e, Request $request) use ($unauthorized) {
            if ($request->is('api/*')) {
                return $unauthorized('请先登录');
            }
        });
    })->create();
