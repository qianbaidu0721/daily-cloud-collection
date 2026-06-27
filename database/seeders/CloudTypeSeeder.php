<?php

namespace Database\Seeders;

use App\Models\CloudType;
use Illuminate\Database\Seeder;

class CloudTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => '积云', 'code' => 'cumulus', 'description' => '棉花状、底部平坦的白色云朵', 'sort' => 1],
            ['name' => '层云', 'code' => 'stratus', 'description' => '均匀灰色云层，常带来阴天', 'sort' => 2],
            ['name' => '卷云', 'code' => 'cirrus', 'description' => '高空细丝状白色云', 'sort' => 3],
            ['name' => '积雨云', 'code' => 'cumulonimbus', 'description' => '高大浓厚，常伴随雷雨', 'sort' => 4],
            ['name' => '层积云', 'code' => 'stratocumulus', 'description' => '灰色或白色块状云层', 'sort' => 5],
            ['name' => '高积云', 'code' => 'altocumulus', 'description' => '中空白色或灰色块状云', 'sort' => 6],
            ['name' => '卷层云', 'code' => 'cirrostratus', 'description' => '薄而透明的白色云幕', 'sort' => 7],
            ['name' => '其他', 'code' => 'other', 'description' => '未分类或其他云型', 'sort' => 99],
        ];

        foreach ($types as $type) {
            CloudType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
