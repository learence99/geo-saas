<?php
// AI 引擎配置（可见度采集器用）。放置：项目根 /config/geo_engines.php
// 采集器会"扮演用户"拿 prompt 去问这些引擎，再解析答案里有没有引用你。
// 有哪个 key 就能用哪个；judge（裁判模型）默认复用 deepseek。
return [
    'engines' => [
        'deepseek' => [
            'name' => 'DeepSeek',
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'key' => env('DEEPSEEK_API_KEY'),
        ],
        'doubao' => [
            'name' => '豆包',
            'base_url' => env('DOUBAO_BASE_URL', 'https://ark.cn-beijing.volces.com/api/v3'),
            'model' => env('DOUBAO_MODEL', ''), // 填推理接入点 ID（ep-xxxx）或模型 ID
            'key' => env('DOUBAO_API_KEY'),
        ],
    ],
    // 裁判模型：解析答案用，便宜的即可
    'judge' => [
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'key' => env('DEEPSEEK_API_KEY'),
    ],
];
