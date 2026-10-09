<?php

use App\Providers\AppServiceProvider;

// Atrium's own providers (atrium-php/core, every installed module) are
// registered through package discovery, before the ones listed here.
return [
    AppServiceProvider::class,
];
