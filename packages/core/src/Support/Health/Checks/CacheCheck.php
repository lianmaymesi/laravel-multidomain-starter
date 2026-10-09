<?php

namespace Atrium\Core\Support\Health\Checks;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Writes, reads back and removes a value on the default cache store. */
class CacheCheck implements Check
{
    public function name(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache';
    }

    public function run(): Result
    {
        $store = config('cache.default');
        $key = 'health:probe:'.Str::random(12);
        $value = Str::random(16);

        Cache::put($key, $value, 60);
        $read = Cache::get($key);
        Cache::forget($key);

        return $read === $value
            ? Result::ok("Store \"{$store}\" reads and writes.", ['store' => $store])
            : Result::failed("Store \"{$store}\" didn't return what was written.", ['store' => $store]);
    }
}
