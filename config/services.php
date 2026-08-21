<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Extractor de CV: convierte el PDF que sube el postulante en campos de su ficha
    // (app/Services/ExtractorCv.php). `proveedor` elige quién lee el documento; sin la
    // api_key de ese proveedor la funcionalidad queda oculta y el flujo manual sigue igual.
    'extractor_cv' => [
        'proveedor' => env('EXTRACTOR_CV_PROVEEDOR', 'gemini'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'modelo' => env('ANTHROPIC_MODELO', 'claude-opus-5'),
    ],

    // Gemini vía Google AI Studio (Interactions API).
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'modelo' => env('GEMINI_MODELO', 'gemini-3.7-flash'),
    ],

    // Pasarela de pago Flow (https://www.flow.cl/docs/api.html).
    // Sandbox: https://sandbox.flow.cl/api — Producción: https://www.flow.cl/api
    'flow' => [
        'api_key' => env('FLOW_API_KEY'),
        'secret_key' => env('FLOW_SECRET_KEY'),
        'base_url' => env('FLOW_BASE_URL', 'https://sandbox.flow.cl/api'),
    ],

];
