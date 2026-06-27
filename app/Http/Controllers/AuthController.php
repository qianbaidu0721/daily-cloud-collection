<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ApiResponse;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:1',
        ], [
            'code.required' => 'code 不能为空',
            'code.string' => 'code 格式无效',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 40001);
        }

        $appId = config('wechat.app_id');
        $secret = config('wechat.secret');

        if (empty($appId) || empty($secret)) {
            Log::error('WeChat mini program credentials are not configured');

            return $this->error('微信服务未配置', 50002, 500);
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 200, throw: false)
                ->get('https://api.weixin.qq.com/sns/jscode2session', [
                    'appid' => $appId,
                    'secret' => $secret,
                    'js_code' => $request->input('code'),
                    'grant_type' => 'authorization_code',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('WeChat jscode2session connection failed', [
                'message' => $e->getMessage(),
            ]);

            return $this->error('微信服务连接超时，请稍后重试', 50001, 503);
        }

        if (! $response->successful()) {
            Log::warning('WeChat jscode2session request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->error('微信服务请求失败', 50003, 502);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return $this->error('微信服务响应异常', 50003, 502);
        }

        if (isset($payload['errcode']) && (int) $payload['errcode'] !== 0) {
            return $this->error(
                $this->mapWeChatError((int) $payload['errcode'], $payload['errmsg'] ?? ''),
                40002
            );
        }

        $openid = $payload['openid'] ?? null;

        if (empty($openid)) {
            return $this->error('微信登录失败，未获取到用户标识', 40003);
        }

        $unionid = $payload['unionid'] ?? null;

        $user = User::firstOrCreate(
            ['openid' => $openid],
            ['unionid' => $unionid]
        );

        if ($unionid && $user->unionid !== $unionid) {
            $user->update(['unionid' => $unionid]);
        }

        $token = JWTAuth::fromUser($user);

        return $this->success([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'openid' => $user->openid,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at?->toDateTimeString(),
        ];
    }

    private function mapWeChatError(int $errcode, string $errmsg): string
    {
        return match ($errcode) {
            40029 => 'code 无效或已过期，请重新登录',
            40163 => 'code 已被使用，请重新获取',
            40226 => '用户登录态异常，请稍后重试',
            45011 => '微信接口调用过于频繁，请稍后重试',
            -1 => '微信系统繁忙，请稍后重试',
            default => $errmsg !== '' ? "微信登录失败：{$errmsg}" : '微信登录失败，请稍后重试',
        };
    }
}
