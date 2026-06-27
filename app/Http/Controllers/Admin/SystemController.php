<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    use ApiResponse;

    /** @var array<string, string> */
    private const CLEAR_COMMANDS = [
        'cache' => 'cache:clear',
        'config' => 'config:clear',
        'route' => 'route:clear',
        'view' => 'view:clear',
    ];

    public function clearCache(): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = auth('admin')->user();

        $results = [];

        foreach (self::CLEAR_COMMANDS as $key => $command) {
            try {
                $exitCode = Artisan::call($command);
                $results[$key] = [
                    'command' => $command,
                    'success' => $exitCode === 0,
                    'output' => trim(Artisan::output()) ?: 'ok',
                ];
            } catch (\Throwable $e) {
                $results[$key] = [
                    'command' => $command,
                    'success' => false,
                    'output' => $e->getMessage(),
                ];
            }
        }

        $allSuccess = collect($results)->every(fn (array $item): bool => $item['success']);

        Log::info('Admin cleared application cache', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'results' => $results,
        ]);

        if (! $allSuccess) {
            return $this->error('部分缓存清除失败，请查看详情', 50020, 500);
        }

        return $this->success([
            'cleared_at' => now()->toDateTimeString(),
            'results' => $results,
        ], '缓存已更新');
    }
}
