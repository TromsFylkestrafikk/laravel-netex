<?php

return [
    /*
    | ------------------------------------------------------------------------
    | Route set
    | ------------------------------------------------------------------------
    |
    | Activate route data this time interval ahead. The period is given as a
    | ISO-8601 interval.
    */
    'activation_period' => env('NETEX_ACTIVATION_PERIOD', 'P30D'),

    /*
    | ------------------------------------------------------------------------
    | Route prefix and middleware
    | ------------------------------------------------------------------------
    |
    | The prefix sets the global prefix for all requests within this package.
    |
    | The middleware lists the middleware this package should consume.
    */
    'routes_api' => [
        'prefix' => 'api/netex',
        'middleware' => ['api'],
    ],
];
