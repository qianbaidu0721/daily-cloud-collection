<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Http\Requests\CloudUploadRequest;
use App\Models\Cloud;
use App\Models\User;
use App\Services\CloudCardService;
use App\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LocationService $locationService,
        private readonly CloudCardService $cloudCardService
    ) {}

    /**
     * 上传云朵图片并保存记录
     *
     * @param  CloudUploadRequest  $request
     * @return JsonResponse
     */
    public function upload(CloudUploadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $collectDate = $request->input('collect_date');

        if (Cloud::where('user_id', $user->id)->whereDate('collect_date', $collectDate)->exists()) {
            return $this->error('今天已经收集过云朵啦，明天再来吧~', 4001);
        }

        $this->ensurePublicStorageReady();

        /** @var UploadedFile $image */
        $image = $request->file('image');
        $date = Carbon::createFromFormat('Y-m-d', $collectDate);
        $directory = sprintf(
            'clouds/%s/%s/%s',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d')
        );

        Storage::disk('public')->makeDirectory($directory);

        $extension = strtolower($image->getClientOriginalExtension() ?: $image->guessExtension() ?: 'jpg');
        $filename = sprintf('%s_%s.%s', $user->id, Str::random(8), $extension);
        $relativePath = $directory.'/'.$filename;

        $storedPath = Storage::disk('public')->putFileAs($directory, $image, $filename);

        if ($storedPath === false) {
            Log::error('Cloud image storage failed', [
                'user_id' => $user->id,
                'path' => $relativePath,
            ]);

            return $this->error('图片存储失败，请稍后重试', 5001, 500);
        }

        try {
            $locationCity = $this->resolveLocationCity($request);

            $cloud = DB::transaction(function () use ($user, $request, $relativePath, $collectDate, $locationCity): Cloud {
                $cloud = Cloud::create([
                    'user_id' => $user->id,
                    'image_path' => $relativePath,
                    'mood' => (int) $request->input('mood'),
                    'mood_label' => $request->input('mood_label'),
                    'location_city' => $locationCity,
                    'location_lat' => $request->input('location_lat'),
                    'location_lng' => $request->input('location_lng'),
                    'note' => $request->input('note'),
                    'cloud_type' => $request->input('cloud_type'),
                    'collect_date' => $collectDate,
                    'is_public' => $request->boolean('is_public'),
                ]);

                $user->increment('total_days');

                return $cloud;
            });
        } catch (QueryException $e) {
            Storage::disk('public')->delete($relativePath);

            if ($this->isDuplicateEntryException($e)) {
                return $this->error('今天已经收集过云朵啦，明天再来吧~', 4001);
            }

            Log::error('Cloud record creation failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error('保存失败，请稍后重试', 5002, 500);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($relativePath);

            Log::error('Cloud upload unexpected error', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error('保存失败，请稍后重试', 5002, 500);
        }

        return $this->success(
            $this->formatCloud($cloud->fresh()),
            '收集成功！☁️'
        );
    }

    /**
     * 查询今日是否已上传云朵
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $today = Carbon::today()->format('Y-m-d');

        $cloud = Cloud::where('user_id', $user->id)
            ->whereDate('collect_date', $today)
            ->first();

        return $this->success([
            'uploaded' => $cloud !== null,
            'collect_date' => $today,
            'cloud' => $cloud ? $this->formatCloud($cloud) : null,
        ]);
    }

    /**
     * 获取用户云朵列表（分页，15 条/页，按日期倒序）
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function listClouds(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $paginator = Cloud::where('user_id', $user->id)
            ->orderByDesc('collect_date')
            ->orderByDesc('id')
            ->paginate(15);

        $items = collect($paginator->items())
            ->map(fn (Cloud $cloud): array => $this->formatCloud($cloud))
            ->values()
            ->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * 获取指定月份的云朵日历数据
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function calendar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        if ($year < 1970 || $year > 2100 || $month < 1 || $month > 12) {
            return $this->error('年月参数无效', 40010);
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $clouds = Cloud::where('user_id', $user->id)
            ->whereBetween('collect_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('collect_date')
            ->get();

        $records = [];
        foreach ($clouds as $cloud) {
            $dateKey = $cloud->collect_date->format('Y-m-d');
            $records[$dateKey] = $this->formatCloud($cloud);
        }

        return $this->success([
            'year' => $year,
            'month' => $month,
            'total_days' => count($records),
            'records' => $records,
        ]);
    }

    /**
     * 查看单条云朵详情
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $cloud = Cloud::where('user_id', $user->id)->where('id', $id)->first();

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        return $this->success($this->formatCloud($cloud));
    }

    /**
     * 生成云朵分享卡片图片
     */
    public function generateCard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'cloud_id' => ['required', 'integer', 'min:1'],
            'force' => ['nullable', 'boolean'],
        ]);

        $cloud = Cloud::where('user_id', $user->id)
            ->where('id', $validated['cloud_id'])
            ->first();

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        try {
            $this->ensurePublicStorageReady();
            Storage::disk('public')->makeDirectory('clouds/cards/'.$user->id);

            $relativePath = $this->cloudCardService->generate(
                $cloud,
                (bool) ($validated['force'] ?? false)
            );

            return $this->success([
                'cloud_id' => $cloud->id,
                'card_path' => $relativePath,
                'card_url' => Storage::disk('public')->url($relativePath).'?v='.(int) config('cloud.card_version', 1),
            ], '卡片生成成功');
        } catch (\Throwable $e) {
            Log::error('Cloud card generation failed', [
                'cloud_id' => $cloud->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('卡片生成失败，请稍后重试', 5002, 500);
        }
    }

    /**
     * 广场：共享云朵列表（分页）
     */
    public function publicPlaza(Request $request): JsonResponse
    {
        $paginator = Cloud::query()
            ->where('is_public', true)
            ->orderByDesc('collect_date')
            ->orderByDesc('id')
            ->paginate(15);

        $items = collect($paginator->items())
            ->map(fn (Cloud $cloud): array => $this->formatPublicCloud($cloud))
            ->values()
            ->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * 广场：共享云朵详情（只读，不含备注）
     */
    public function publicDetail(Request $request, int $id): JsonResponse
    {
        $cloud = Cloud::where('id', $id)->where('is_public', true)->first();

        if ($cloud === null) {
            return $this->error('云朵不存在或未共享', 40401, 404);
        }

        return $this->success($this->formatPublicCloud($cloud));
    }

    /**
     * 切换单条云朵共享状态
     */
    public function updateVisibility(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'is_public' => ['required', 'boolean'],
        ]);

        $cloud = Cloud::where('user_id', $user->id)->where('id', $id)->first();

        if ($cloud === null) {
            return $this->error('云朵记录不存在', 40401, 404);
        }

        $cloud->update(['is_public' => (bool) $validated['is_public']]);

        return $this->success(
            $this->formatCloud($cloud->fresh()),
            $cloud->is_public ? '已共享到广场' : '已设为仅自己可见'
        );
    }

    /**
     * 一键将本人全部私有云朵设为共享
     */
    public function batchShare(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'scope' => ['required', 'in:all'],
        ]);

        $updatedCount = Cloud::where('user_id', $user->id)
            ->where('is_public', false)
            ->update(['is_public' => true]);

        return $this->success(
            ['updated_count' => $updatedCount],
            $updatedCount > 0 ? "已共享 {$updatedCount} 朵云" : '没有可共享的私有云朵'
        );
    }

    /**
     * 格式化云朵记录输出（本人可见，含私有字段）
     */
    private function formatCloud(Cloud $cloud): array
    {
        return [
            'id' => $cloud->id,
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

    /**
     * 格式化广场云朵（匿名，不含备注与坐标）
     *
     * @return array<string, mixed>
     */
    private function formatPublicCloud(Cloud $cloud): array
    {
        return [
            'id' => $cloud->id,
            'image_url' => Storage::disk('public')->url($cloud->image_path),
            'mood' => $cloud->mood,
            'mood_label' => $cloud->mood_label,
            'location_city' => $cloud->location_city,
            'cloud_type' => $cloud->cloud_type,
            'collect_date' => $cloud->collect_date->format('Y-m-d'),
            'author_label' => '某位云友',
        ];
    }

    /**
     * 确保 public 存储目录存在
     */
    private function ensurePublicStorageReady(): void
    {
        $publicRoot = storage_path('app/public');

        if (! is_dir($publicRoot)) {
            mkdir($publicRoot, 0755, true);
        }

        Storage::disk('public')->makeDirectory('clouds');
        Storage::disk('public')->makeDirectory('clouds/cards');

        $cardsRoot = storage_path('app/public/clouds/cards');
        if (! is_dir($cardsRoot)) {
            mkdir($cardsRoot, 0755, true);
        }
    }

    /**
     * 判断是否为唯一约束冲突异常
     */
    private function isDuplicateEntryException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || $e->getCode() === '23000';
    }

    /**
     * 解析 location_city：前端未传时根据经纬度逆解析
     */
    private function resolveLocationCity(CloudUploadRequest $request): ?string
    {
        $locationCity = $request->input('location_city');

        if (filled($locationCity)) {
            return $locationCity;
        }

        if (! $request->filled('location_lat') || ! $request->filled('location_lng')) {
            return null;
        }

        return $this->locationService->resolveCityName(
            (float) $request->input('location_lat'),
            (float) $request->input('location_lng')
        );
    }
}
