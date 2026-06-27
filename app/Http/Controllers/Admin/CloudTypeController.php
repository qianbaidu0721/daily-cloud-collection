<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Http\Requests\Admin\StoreCloudTypeRequest;
use App\Http\Requests\Admin\UpdateCloudTypeRequest;
use App\Models\Cloud;
use App\Models\CloudType;
use Illuminate\Http\JsonResponse;

class CloudTypeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $list = CloudType::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (CloudType $type): array => $this->formatCloudType($type))
            ->values()
            ->all();

        return $this->success(['list' => $list]);
    }

    public function store(StoreCloudTypeRequest $request): JsonResponse
    {
        $type = CloudType::create([
            ...$request->validated(),
            'sort' => $request->input('sort', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->success($this->formatCloudType($type), '创建成功');
    }

    public function update(UpdateCloudTypeRequest $request, int $id): JsonResponse
    {
        $type = CloudType::find($id);

        if ($type === null) {
            return $this->error('云类型不存在', 40401, 404);
        }

        $type->update($request->validated());

        return $this->success($this->formatCloudType($type->fresh()), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $type = CloudType::find($id);

        if ($type === null) {
            return $this->error('云类型不存在', 40401, 404);
        }

        $inUse = Cloud::where('cloud_type', $type->code)->exists();

        if ($inUse) {
            return $this->error('该云类型已被云朵记录引用，无法删除', 40001);
        }

        $type->delete();

        return $this->success(null, '删除成功');
    }

    private function formatCloudType(CloudType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'code' => $type->code,
            'description' => $type->description,
            'icon' => $type->icon,
            'sort' => (int) $type->sort,
            'is_active' => (bool) $type->is_active,
            'created_at' => $type->created_at?->toDateTimeString(),
            'updated_at' => $type->updated_at?->toDateTimeString(),
        ];
    }
}
