<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del Panel Administrativo
    |--------------------------------------------------------------------------
    */

    // Configuración de autenticación
    'auth' => [
        'session_lifetime' => env('AUTH_SESSION_LIFETIME', 120),
        'hash_driver' => env('AUTH_HASH_DRIVER', 'bcrypt'),
        'login_route' => env('ADMIN_LOGIN_ROUTE', '/login'),
        'dashboard_route' => env('ADMIN_DASHBOARD_ROUTE', '/dashboard'),
        'logout_route' => env('ADMIN_LOGOUT_ROUTE', '/logout'),
    ],

    // Configuración de BigQuery
    'bigquery' => [
        'project_id' => env('BIGQUERY_PROJECT_ID', 'adoc-bi-dev'),
        'dataset' => env('BIGQUERY_ADMIN_DATASET', 'OPB'),
        'usuarios_table' => env('BIGQUERY_USUARIOS_TABLE', 'usuarios'),
        'visitas_table' => env('BIGQUERY_VISITAS_TABLE', 'GR_nuevo'),
        'key_file' => env('BIGQUERY_KEY_FILE', '/claves/adoc-bi-dev-debcb06854ae.json'),
    ],

    // Configuración de paginación
    'pagination' => [
        'per_page' => env('ADMIN_PAGINATION_SIZE', 20),
        'max_per_page' => 100,
    ],

    // Configuración de exportación
    'export' => [
        'excel_timeout' => env('EXCEL_EXPORT_TIMEOUT', 300),
        'max_rows' => env('EXCEL_MAX_ROWS', 10000),
        'chunk_size' => 1000,
    ],

    // Roles disponibles
    'roles' => [
        'admin' => 'Administrador',
        'evaluador' => 'Evaluador',
        'evaluador_pais' => 'Evaluador por País',
    ],
    
    // Países disponibles para restricción de acceso
    'paises_disponibles' => [
        'ALL' => 'Todos los países',
        'GT' => 'Guatemala',
        'SV' => 'El Salvador', 
        'HN' => 'Honduras',
        'NI' => 'Nicaragua',
        'CR' => 'Costa Rica',
        'PA' => 'Panamá',
    ],
    
    // Configuración de acceso por país
    'acceso_pais' => [
        'roles_con_restriccion' => ['evaluador_pais'],
        'roles_sin_restriccion' => ['admin', 'evaluador'],
    ],    

    // Filtros disponibles
    'filters' => [
        'date_format' => 'Y-m-d',
        'datetime_format' => 'Y-m-d H:i:s',
        'max_date_range_days' => 365,
    ],
    // Configuración de seguridad
    'security' => [
        'login_attempts' => env('ADMIN_LOGIN_ATTEMPTS', 5),
        'lockout_minutes' => env('ADMIN_LOCKOUT_MINUTES', 5),
        'session_timeout' => env('ADMIN_SESSION_TIMEOUT', 120),
    ],

];