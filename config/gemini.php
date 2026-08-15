<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gemini API Configuration
    |--------------------------------------------------------------------------
    */
    'api_key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    /*
    |--------------------------------------------------------------------------
    | Default available models
    |--------------------------------------------------------------------------
    */
    'models' => [
        'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast & Cost Effective)',
        'gemini-1.5-pro' => 'Gemini 1.5 Pro (High Quality)',
        'gemini-1.0-pro' => 'Gemini 1.0 Pro (Legacy)',
        'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash Experimental',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generation defaults
    |--------------------------------------------------------------------------
    */
    'temperature' => 0.7,
    'max_output_tokens' => 2048,
    'top_p' => 0.95,
    'top_k' => 64,

    /*
    |--------------------------------------------------------------------------
    | Chat history limit
    |--------------------------------------------------------------------------
    */
    'history_limit' => 20, // last N messages to keep context

    /*
    |--------------------------------------------------------------------------
    | Store data cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'context_cache_ttl' => 300, // 5 minutes
];
