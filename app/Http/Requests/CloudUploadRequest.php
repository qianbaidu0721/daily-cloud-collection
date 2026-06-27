<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CloudUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],
            'mood' => ['required', 'integer', 'between:1,5'],
            'mood_label' => ['nullable', 'string', 'max:32'],
            'location_city' => ['nullable', 'string', 'max:64'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:500'],
            'cloud_type' => [
                'nullable',
                'string',
                'max:32',
                Rule::exists('cloud_types', 'name')->where('is_active', true),
            ],
            'collect_date' => ['required', 'date_format:Y-m-d'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => '请上传云朵图片',
            'image.file' => '图片格式无效',
            'image.mimes' => '图片格式不支持，仅支持 jpeg、jpg、png',
            'image.max' => '图片大小不能超过 5MB',
            'mood.required' => '请选择心情',
            'mood.integer' => '心情格式无效',
            'mood.between' => '心情值需在 1-5 之间',
            'collect_date.required' => '请选择收集日期',
            'collect_date.date_format' => '收集日期格式无效，需为 Y-m-d',
            'location_lat.between' => '纬度范围无效',
            'location_lng.between' => '经度范围无效',
            'note.max' => '备注不能超过 500 字',
            'cloud_type.exists' => '云类型无效或已停用',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        $code = 40000;
        $msg = $errors->first();

        if ($errors->has('image')) {
            $imageError = $errors->first('image');
            if (str_contains($imageError, '5MB') || str_contains($imageError, 'max')) {
                $code = 4003;
            } elseif (str_contains($imageError, '格式') || str_contains($imageError, 'mimes')) {
                $code = 4002;
            }
            $msg = $imageError;
        }

        throw new HttpResponseException(response()->json([
            'code' => $code,
            'msg' => $msg,
            'data' => null,
        ], 422));
    }
}
