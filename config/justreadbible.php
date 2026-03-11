<?php

return [
    'domain' => env('JUST_READ_BIBLE_DOMAIN', 'localhost'),

    'domains' => [
        'user' => 'app' . env('JUST_READ_BIBLE_DOMAIN', 'localhost'),
        'admin' => 'admin' . env('JUST_READ_BIBLE_DOMAIN', 'localhost'),
        'backoffice' => 'backoffice' . env('JUST_READ_BIBLE_DOMAIN', 'localhost'),
        'accounts' => 'accounts' . env('JUST_READ_BIBLE_DOMAIN', 'localhost'),
        'auth' => 'auth' . env('JUST_READ_BIBLE_DOMAIN', 'localhost'),
    ],
];
