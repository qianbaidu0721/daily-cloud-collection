<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 分享卡片字体（可选）
    | 不配置则自动使用 resources/fonts/ 或 storage/app/fonts/ 下任意 .ttf/.otf/.ttc
    |--------------------------------------------------------------------------
    */
    'card_font' => env('CLOUD_CARD_FONT'),

    /*
    |--------------------------------------------------------------------------
    | 常用字体文件名（可选，会自动扫描 fonts 目录，文件名不限）
    |--------------------------------------------------------------------------
    */
    'card_font_candidates' => [
        resource_path('fonts/NotoSansSC-Regular.otf'),
        resource_path('fonts/NotoSansSC-Regular.ttf'),
        resource_path('fonts/NotoSansSC-VariableFont_wght.ttf'),
        resource_path('fonts/msyh.ttc'),
        resource_path('fonts/msyh.ttf'),
        resource_path('fonts/simhei.ttf'),
        resource_path('fonts/SourceHanSansSC-Regular.otf'),
    ],
    'card_width' => 1080,
    'card_height' => 1440,

    /*
    | 卡片模板版本：样式改版时递增，自动使旧缓存失效
    */
    'card_version' => 3,

    /*
    |--------------------------------------------------------------------------
    | 心情展示（与小程序 weather mood 映射一致）
    | short 用于卡片左侧圆形徽章（服务器字体通常无法渲染 Emoji）
    |--------------------------------------------------------------------------
    */
    'mood_labels' => [
        1 => 'emo',
        2 => '一般',
        3 => '平静',
        4 => '开心',
        5 => '超开心',
    ],

    'mood_display' => [
        1 => ['label' => 'emo', 'short' => '雨'],
        2 => ['label' => '一般', 'short' => '云'],
        3 => ['label' => '平静', 'short' => '静'],
        4 => ['label' => '开心', 'short' => '晴'],
        5 => ['label' => '超开心', 'short' => '阳'],
    ],
];
