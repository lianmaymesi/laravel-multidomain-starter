<?php

namespace Atrium\Core\Concerns;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Rate limits for Livewire actions, using the limits in config/rate-limits.php.
 * Route `throttle` middleware can't do this: Livewire posts every action to
 * /livewire/update, not to the page's route.
 *
 *   // Count every attempt (sending mail/SMS, creating accounts):
 *   $this->throttle('register', request()->ip(), 'email');
 *
 *   // Count only failures, forgive on success (login, codes):
 *   $this->ensureNotThrottled('login', $key, 'email');
 *   if (! $ok) { $this->hitThrottle('login', $key); throw ...; }
 *   $this->clearThrottle('login', $key);
 *
 * Over the limit, a ValidationException is thrown on $field with a friendly
 * "try again in 4 minutes" message, so it shows like any other field error.
 */
trait ThrottlesActions
{
    /** Check, then count this attempt. */
    protected function throttle(string $limit, string $key, string $field): void
    {
        $this->ensureNotThrottled($limit, $key, $field);
        $this->hitThrottle($limit, $key);
    }

    /** @throws ValidationException when $key has used up $limit */
    protected function ensureNotThrottled(string $limit, string $key, string $field): void
    {
        $id = $this->throttleId($limit, $key);

        if (! RateLimiter::tooManyAttempts($id, $this->throttleMax($limit))) {
            return;
        }

        throw ValidationException::withMessages([
            $field => __('Too many attempts. Please try again in :time.', [
                'time' => $this->throttleWait(RateLimiter::availableIn($id)),
            ]),
        ]);
    }

    protected function hitThrottle(string $limit, string $key): void
    {
        RateLimiter::hit($this->throttleId($limit, $key), (int) config("rate-limits.{$limit}.decay", 60));
    }

    protected function clearThrottle(string $limit, string $key): void
    {
        RateLimiter::clear($this->throttleId($limit, $key));
    }

    private function throttleId(string $limit, string $key): string
    {
        // Hashed: keys often contain emails, which don't belong in cache keys.
        return "throttle:{$limit}:".sha1(mb_strtolower($key));
    }

    private function throttleMax(string $limit): int
    {
        return (int) config("rate-limits.{$limit}.max", 5);
    }

    private function throttleWait(int $seconds): string
    {
        return CarbonInterval::seconds(max($seconds, 1))->cascade()->forHumans(['parts' => 2]);
    }
}
