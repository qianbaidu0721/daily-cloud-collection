<?php

use App\Http\Controllers\Api\CloudController;
use App\Http\Controllers\Api\CloudTypeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 路由（小程序统一使用 /api/v1/*）
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]));

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/cloud-types', [CloudTypeController::class, 'index']);
        Route::get('/location/reverse', [LocationController::class, 'reverse']);

        Route::prefix('clouds')->group(function () {
            Route::post('/upload', [CloudController::class, 'upload']);
            Route::get('/today', [CloudController::class, 'today']);
            Route::get('/calendar', [CloudController::class, 'calendar']);
            Route::get('/public', [CloudController::class, 'publicPlaza']);
            Route::get('/public/{id}', [CloudController::class, 'publicDetail'])->whereNumber('id');
            Route::post('/batch-share', [CloudController::class, 'batchShare']);
            Route::post('/card', [CloudController::class, 'generateCard']);
            Route::patch('/{id}/visibility', [CloudController::class, 'updateVisibility'])->whereNumber('id');
            Route::get('/', [CloudController::class, 'listClouds']);
            Route::get('/{id}', [CloudController::class, 'detail'])->whereNumber('id');
        });
    });
});

/*
|--------------------------------------------------------------------------
| 兼容旧路径（无 v1 前缀）
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'ok', 'deprecated' => '请使用 /api/v1/health']));

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/location/reverse', [LocationController::class, 'reverse']);

    Route::prefix('clouds')->group(function () {
        Route::post('/upload', [CloudController::class, 'upload']);
        Route::get('/today', [CloudController::class, 'today']);
        Route::get('/calendar', [CloudController::class, 'calendar']);
        Route::get('/', [CloudController::class, 'listClouds']);
        Route::get('/{id}', [CloudController::class, 'detail'])->whereNumber('id');
    });
});

require __DIR__.'/admin.php';
