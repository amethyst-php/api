<?php

return [
    'http' => [
        'app' => [
            'router'     => [
                'as'   => 'app.',
                'prefix' => '/api/v1/ore',
            ],
        ],
        'admin' => [
            'router' => [
                'as'   => 'admin.',
                'prefix' => '/api/v1/ore/admin',
            ],
        ],
    ],
];
