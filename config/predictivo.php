<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Predictive ML engine
    |--------------------------------------------------------------------------
    |
    | The engine is a Python process invoked over stdin/stdout with one JSON
    | payload. It is never an HTTP service. Relative paths are resolved from
    | the application base path.
    |
    */
    'ml_engine' => [
        'binary' => env('PREDICTIVO_ML_BINARY', 'python3'),
        'script' => env('PREDICTIVO_ML_SCRIPT', base_path('ml-engine/main.py')),
        'timeout' => (int) env('PREDICTIVO_ML_TIMEOUT', 20),
        'training_timeout' => (int) env('PREDICTIVO_ML_TRAINING_TIMEOUT', 300),
    ],
];
