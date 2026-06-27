<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Models\AdminUser;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        $setupError = $this->resolveSetupError();
        if ($setupError !== null) {
            return $setupError;
        }

        $credentials = $request->only('email', 'password');

        try {
            /** @var AdminUser|null $admin */
            $admin = AdminUser::where('email', $credentials['email'])->first();

            if ($admin === null || ! Hash::check($credentials['password'], $admin->password)) {
                return $this->error('邮箱或密码错误', 40101, 401);
            }

            if (! $admin->is_active) {
                return $this->error('账号已被禁用', 40301, 403);
            }

            $token = auth('admin')->login($admin);
            $admin->update(['last_login_at' => now()]);

            return $this->success([
                'token' => $token,
                'admin' => $this->formatAdmin($admin),
            ], '登录成功');
        } catch (QueryException $e) {
            Log::error('Admin login database error', [
                'message' => $e->getMessage(),
            ]);

            return $this->error('数据库异常，请检查连接与迁移是否完成', 50011, 503);
        } catch (\InvalidArgumentException $e) {
            Log::error('Admin login guard error', [
                'message' => $e->getMessage(),
            ]);

            return $this->error(
                'admin 认证守卫未配置，请上传 config/auth.php 后执行 php artisan config:clear',
                50014,
                503
            );
        } catch (\Throwable $e) {
            Log::error('Admin login failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $msg = config('app.debug')
                ? '登录失败：'.$e->getMessage()
                : '登录服务异常，请稍后重试';

            return $this->error($msg, 50012, 500);
        }
    }

    public function me(): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = auth('admin')->user();

        return $this->success($this->formatAdmin($admin));
    }

    public function logout(): JsonResponse
    {
        auth('admin')->logout();

        return $this->success(null, '已退出登录');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = auth('admin')->user();

        if (! Hash::check($request->input('current_password'), $admin->password)) {
            return $this->error('当前密码不正确', 40001);
        }

        $admin->update(['password' => $request->input('password')]);

        auth('admin')->logout();

        return $this->success(null, '密码已更新，请重新登录');
    }

    private function resolveSetupError(): ?JsonResponse
    {
        if (! Schema::hasTable('admin_users')) {
            return $this->error('管理员表未初始化，请在服务器执行 php artisan migrate', 50010, 503);
        }

        if (! class_exists(AdminUser::class)) {
            return $this->error('AdminUser 模型未部署，请上传 app/Models/AdminUser.php', 50013, 503);
        }

        if (! array_key_exists('admin', config('auth.guards', []))) {
            return $this->error(
                'admin 守卫未配置，请上传 config/auth.php 后执行 php artisan config:clear',
                50014,
                503
            );
        }

        if (AdminUser::count() === 0) {
            return $this->error(
                '尚无管理员账号，请执行 php artisan db:seed --class=AdminUserSeeder',
                50015,
                503
            );
        }

        return null;
    }

    private function formatAdmin(AdminUser $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'last_login_at' => $admin->last_login_at?->toDateTimeString(),
        ];
    }
}
