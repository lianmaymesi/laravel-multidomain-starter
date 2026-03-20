<?php

return [
    'main_domain' => env('JUST_READ_BIBLE_MAIN_DOMAIN', 'localhost'),

    'sub_domains' => [
        'app' => 'app.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'backoffice' => 'backoffice.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'landing' => 'landing.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'account' => 'account.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'auth' => 'auth.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'api' => 'api.' . env('JUST_READ_BIBLE_MAIN_DOMAIN'),
    ]
];
