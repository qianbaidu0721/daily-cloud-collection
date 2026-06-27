<?php

return [
    'key' => env('AMAP_KEY'),
    'geocode_url' => 'https://restapi.amap.com/v3/geocode/regeo',
    'timeout' => (int) env('AMAP_TIMEOUT', 5),
];
