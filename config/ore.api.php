<?php

return [
    'http' => [
        'app' => [
            'router'     => [
                'name'   => 'app',
                'prefix' => 'api/v1/ore/',
            ],
        ],
        'admin' => [
            'router' => [
                'name'   => 'admin',
                'prefix' => 'api/v1/ore/admin/',
            ],
        ],
    ],
];
