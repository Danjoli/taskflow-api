<?php

return [
    'task_cache_ttl' => (int) env('TASK_CACHE_TTL', 300),

    'rate_limits' => [
        'login_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 5),
        'register_per_hour' => (int) env('RATE_LIMIT_REGISTER_PER_HOUR', 3),
        'api_per_minute' => (int) env('RATE_LIMIT_API_PER_MINUTE', 120),
    ],
];
