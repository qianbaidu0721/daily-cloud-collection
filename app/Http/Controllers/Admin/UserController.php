<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Models\Cloud;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $keyword = trim((string) $request->input('keyword', ''));

        $query = User::query()->orderByDesc('id');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('nickname', 'like', "%{$keyword}%")
                    ->orWhere('openid', 'like', "%{$keyword}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $list = collect($paginator->items())
            ->map(fn (User $user): array => $this->formatUserSummary($user))
            ->values()
            ->all();

        return $this->success([
            'list' => $list,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if ($user === null) {
            return $this->error('用户不存在', 40401, 404);
        }

        $cloudCount = Cloud::where('user_id', $user->id)->count();
        $publicCount = Cloud::where('user_id', $user->id)->where('is_public', true)->count();

        return $this->success([
            ...$this->formatUserSummary($user),
            'clouds_count' => $cloudCount,
            'public_clouds_count' => $publicCount,
            'created_at' => $user->created_at?->toDateTimeString(),
        ]);
    }

    public function clouds(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if ($user === null) {
            return $this->error('用户不存在', 40401, 404);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $paginator = Cloud::query()
            ->where('user_id', $user->id)
            ->orderByDesc('collect_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        $list = collect($paginator->items())
            ->map(fn (Cloud $cloud): array => $this->formatCloudSummary($cloud))
            ->values()
            ->all();

        return $this->success([
            'list' => $list,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    private function formatUserSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'openid' => $this->maskOpenid($user->openid),
            'total_days' => (int) $user->total_days,
        ];
    }

    private function formatCloudSummary(Cloud $cloud): array
    {
        return [
            'id' => $cloud->id,
            'image_url' => Storage::disk('public')->url($cloud->image_path),
            'mood' => $cloud->mood,
            'mood_label' => $cloud->mood_label,
            'location_city' => $cloud->location_city,
            'cloud_type' => $cloud->cloud_type,
            'collect_date' => $cloud->collect_date->format('Y-m-d'),
            'is_public' => (bool) $cloud->is_public,
        ];
    }

    private function maskOpenid(?string $openid): ?string
    {
        if ($openid === null || $openid === '') {
            return $openid;
        }

        if (strlen($openid) <= 8) {
            return str_repeat('*', strlen($openid));
        }

        return substr($openid, 0, 4).'***'.substr($openid, -4);
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
