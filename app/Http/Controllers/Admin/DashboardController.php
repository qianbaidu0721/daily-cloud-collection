<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Models\Cloud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function overview(): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        return $this->success([
            'users_total' => User::count(),
            'clouds_total' => Cloud::count(),
            'clouds_today' => Cloud::whereDate('collect_date', $today)->count(),
            'public_clouds_total' => Cloud::where('is_public', true)->count(),
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $days = min(max((int) $request->input('days', 7), 1), 90);
        $start = Carbon::today()->subDays($days - 1)->toDateString();
        $end = Carbon::today()->toDateString();

        $counts = Cloud::query()
            ->select('collect_date', DB::raw('COUNT(*) as count'))
            ->whereBetween('collect_date', [$start, $end])
            ->groupBy('collect_date')
            ->pluck('count', 'collect_date');

        $items = [];
        $cursor = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        while ($cursor->lte($endDate)) {
            $dateKey = $cursor->toDateString();
            $items[] = [
                'date' => $dateKey,
                'count' => (int) ($counts[$dateKey] ?? 0),
            ];
            $cursor->addDay();
        }

        return $this->success(['items' => $items]);
    }
}
