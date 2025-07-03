<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Servicios Google Cloud (Unificado)
    |--------------------------------------------------------------------------
    */

    'google' => [
        'project_id' => env('BIGQUERY_PROJECT_ID', 'adoc-bi-dev'),
        'keyfile' => env('BIGQUERY_KEY_FILE', '/claves/adoc-bi-dev-debcb06854ae.json'),
        'storage_bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'adoc-bi-dev-control-visitas-lz'),
        'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', 'https://storage.googleapis.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración BigQuery Admin
    |--------------------------------------------------------------------------
    */

    'bigquery_admin' => [
        'project_id' => env('BIGQUERY_PROJECT_ID', 'adoc-bi-dev'),
        'dataset' => env('BIGQUERY_ADMIN_DATASET', 'OPB'),
        'usuarios_table' => env('BIGQUERY_USUARIOS_TABLE', 'usuarios'),
        'visitas_table' => env('BIGQUERY_VISITAS_TABLE', 'GR_nuevo'),
        // Tabla alternativa con campos anidados
        'visitas_nested_table' => env('BIGQUERY_VISITAS_NESTED_TABLE', 'GR_nested'),
    ],

];