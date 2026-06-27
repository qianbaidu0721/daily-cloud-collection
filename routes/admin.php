<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CloudController;
use App\Http\Controllers\Admin\CloudTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| 管理后台 API（/api/admin/v1/*）
|--------------------------------------------------------------------------
*/
Route::prefix('admin/v1')->group(function () {
    Route::get('/health', function () {
        $checks = [
            'admin_users_table' => Schema::hasTable('admin_users'),
            'admin_user_model' => class_exists(\App\Models\AdminUser::class),
            'admin_guard' => array_key_exists('admin', config('auth.guards', [])),
            'admin_users_count' => 0,
        ];

        if ($checks['admin_users_table'] && $checks['admin_user_model']) {
            try {
                $checks['admin_users_count'] = \App\Models\AdminUser::count();
            } catch (\Throwable) {
                $checks['admin_users_count'] = -1;
            }
        }

        $ready = $checks['admin_users_table']
            && $checks['admin_user_model']
            && $checks['admin_guard']
            && $checks['admin_users_count'] > 0;

        return response()->json([
            'status' => 'ok',
            'scope' => 'admin',
            'ready' => $ready,
            'checks' => $checks,
        ], $ready ? 200 : 503);
    });

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:admin', 'admin.token'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);

        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/dashboard/trends', [DashboardController::class, 'trends']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id');
        Route::get('/users/{id}/clouds', [UserController::class, 'clouds'])->whereNumber('id');

        Route::get('/clouds', [CloudController::class, 'index']);
        Route::get('/clouds/{id}', [CloudController::class, 'show'])->whereNumber('id');
        Route::patch('/clouds/{id}', [CloudController::class, 'update'])->whereNumber('id');
        Route::delete('/clouds/{id}', [CloudController::class, 'destroy'])->whereNumber('id');

        Route::get('/cloud-types', [CloudTypeController::class, 'index']);
        Route::post('/cloud-types', [CloudTypeController::class, 'store']);
        Route::put('/cloud-types/{id}', [CloudTypeController::class, 'update'])->whereNumber('id');
        Route::delete('/cloud-types/{id}', [CloudTypeController::class, 'destroy'])->whereNumber('id');

        Route::post('/system/clear-cache', [SystemController::class, 'clearCache'])
            ->middleware('throttle:5,1');
    });
});
