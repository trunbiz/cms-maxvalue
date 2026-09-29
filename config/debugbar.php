<?php

return [
    'enabled' => env('APP_ENV', 'production') === 'local' && env('DEBUGBAR_ENABLED', false),
    'except' => ['api/*'],
    'storage' => ['enabled' => false],
];
