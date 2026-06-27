<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Http\Requests\Admin\UpdateCloudRequest;
use App\Models\Cloud;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CloudController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $query = Cloud::query()
            ->with('user:id,nickname,avatar,openid')
            ->orderByDesc('collect_date')
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('is_public')) {
            $query->where('is_public', filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('mood')) {
            $query->where('mood', (int) $request->input('mood'));
        }

        if ($request->filled('cloud_type')) {
            $query->where('cloud_type', (string) $request->input('cloud_type'));
        }

        if ($request->filled('collect_date')) {
            $query->whereDate('collect_date', (string) $request->input('collect_date'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('location_city', 'like', "%{$keyword}%")
                    ->orWhere('note', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function ($userQuery) use ($keyword): void {
                        $userQuery->where('nickname', 'like', "%{$keyword}%");
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        $list = collect($paginator->items())
            ->map(fn (Cloud $cloud): array => $this->formatCloudSummary($cloud))
            ->values()
            ->all();

        return $this->success([
            'list' => $list,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $cloud = Cloud::with('user:id,nickname,avatar,openid,total_days')->find($id);

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        return $this->success($this->formatCloudDetail($cloud));
    }

    public function update(UpdateCloudRequest $request, int $id): JsonResponse
    {
        $cloud = Cloud::find($id);

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        $cloud->update($request->validated());

        return $this->success($this->formatCloudDetail($cloud->fresh('user')), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $cloud = Cloud::find($id);

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        $imagePath = $cloud->image_path;
        $cloud->delete();

        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        return $this->success(null, '删除成功');
    }

    private function formatCloudSummary(Cloud $cloud): array
    {
        /** @var User|null $user */
        $user = $cloud->user;

        return [
            'id' => $cloud->id,
            'user_id' => $cloud->user_id,
            'user_nickname' => $user?->nickname,
            'image_url' => Storage::disk('public')->url($cloud->image_path),
            'mood' => $cloud->mood,
            'mood_label' => $cloud->mood_label,
            'location_city' => $cloud->location_city,
            'cloud_type' => $cloud->cloud_type,
            'collect_date' => $cloud->collect_date->format('Y-m-d'),
            'is_public' => (bool) $cloud->is_public,
            'created_at' => $cloud->created_at?->toDateTimeString(),
        ];
    }

    private function formatCloudDetail(Cloud $cloud): array
    {
        /** @var User|null $user */
        $user = $cloud->user;

        return [
            'id' => $cloud->id,
            'user_id' => $cloud->user_id,
            'user' => $user ? [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'total_days' => (int) $user->total_days,
            ] : null,
            'image_path' => $cloud->image_path,
            'image_url' => Storage::disk('public')->url($cloud->image_path),
            'mood' => $cloud->mood,
            'mood_label' => $cloud->mood_label,
            'location_city' => $cloud->location_city,
            'location_lat' => $cloud->location_lat,
            'location_lng' => $cloud->location_lng,
            'note' => $cloud->note,
            'cloud_type' => $cloud->cloud_type,
            'collect_date' => $cloud->collect_date->format('Y-m-d'),
            'is_public' => (bool) $cloud->is_public,
            'created_at' => $cloud->created_at?->toDateTimeString(),
            'updated_at' => $cloud->updated_at?->toDateTimeString(),
        ];
    }

    private function paginationMeta($paginator): array
    {
        return [
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
