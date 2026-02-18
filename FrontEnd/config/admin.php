<?php
return [
    'bigquery' => [
        'project_id' => env('BIGQUERY_PROJECT_ID', 'adoc-bi-dev'),
        'dataset' => env('BIGQUERY_ADMIN_DATASET', 'OPB'),
        'usuarios_table' => env('BIGQUERY_USUARIOS_TABLE', 'usuarios'),
        'visitas_table' => env('BIGQUERY_VISITAS_TABLE', 'GR_pruebas'),
        'key_file' => env('BIGQUERY_KEY_FILE', '/claves/adoc-bi-dev-debcb06854ae.json'),
    ],
];
