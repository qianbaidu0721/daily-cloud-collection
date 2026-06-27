<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $msg = '成功', int $code = 0): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    protected function error(string $msg, int $code, int $httpStatus = 400): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg' => $msg,
            'data' => null,
        ], $httpStatus);
    }
}
