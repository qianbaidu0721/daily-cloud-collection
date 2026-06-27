<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Models\CloudType;
use Illuminate\Http\JsonResponse;

class CloudTypeController extends Controller
{
    use ApiResponse;

    /**
     * 获取启用的云类型列表（按 sort 排序）
     */
    public function index(): JsonResponse
    {
        $items = CloudType::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'description', 'icon'])
            ->map(fn (CloudType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'description' => $type->description,
                'icon' => $type->icon,
            ])
            ->values()
            ->all();

        return $this->success(['items' => $items]);
    }
}
