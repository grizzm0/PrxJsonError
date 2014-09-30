<?php
namespace Prx\JsonError;

use Prx\JsonError\Listener\JsonErrorListener;

return [
    'service_manager' => [
        'invokables' => [
            JsonErrorListener::class => JsonErrorListener::class
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
