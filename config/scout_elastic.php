<?php

return [
    'client' => [
        'hosts' => [
            [
                'host' => env('SCOUT_ELASTIC_HOST', 'elasticsearch'),
                'port' => env('SCOUT_ELASTIC_PORT', '9200'),
                'scheme' => env('SCOUT_ELASTIC_SCHEME', 'http'),
                'user' => env('SCOUT_ELASTIC_USERNAME'),
                'pass' => env('SCOUT_ELASTIC_PASSWORD'),
            ],
        ],
    ],
    'update_mapping' => env('SCOUT_ELASTIC_UPDATE_MAPPING', true),
    'indexer' => env('SCOUT_ELASTIC_INDEXER', 'single'),
    'document_refresh' => env('SCOUT_ELASTIC_DOCUMENT_REFRESH', true),
];
