<?php

return [
    /*
    | ------------------------------------------------------------------------
    | Route prefix and middleware
    | ------------------------------------------------------------------------
    |
    | The prefix sets the global prefix for all requests within this package.
    | Defaults to 'netex'.
    |
    | The middleware lists the middleware this package should consume.
    */
    'routes_api' => [
        'prefix' => 'api/netex',
        'middleware' => ['api'],
    ],
];
