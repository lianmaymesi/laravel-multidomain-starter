<?php

namespace App\Support;

class ErrorPageMeta
{
    /**
     * Display copy for an error/maintenance code. "maintenance" is treated
     * as a pseudo-code alongside real HTTP status codes so the same error
     * page template can render it.
     *
     * @return array{title: string, description: string}
     */
    public static function for(string|int $code): array
    {
        return match ((string) $code) {
            '401' => ['title' => 'Unauthorized', 'description' => 'You need to sign in to view this page.'],
            '403' => ['title' => 'Forbidden', 'description' => "You don't have permission to access this page."],
            '404' => ['title' => 'Page not found', 'description' => "The page you're looking for doesn't exist or has moved."],
            '412' => ['title' => 'Precondition failed', 'description' => 'This request could not be processed as sent. Please try again.'],
            '419' => ['title' => 'Page expired', 'description' => 'Your session expired. Please refresh and try again.'],
            '429' => ['title' => 'Too many requests', 'description' => "You've made too many requests. Please wait a moment and try again."],
            '500' => ['title' => 'Server error', 'description' => 'Something went wrong on our end. Please try again shortly.'],
            '503' => ['title' => 'Service unavailable', 'description' => "We're temporarily unable to handle this request. Please try again shortly."],
            'maintenance' => ['title' => 'Under maintenance', 'description' => "We're making some improvements. We'll be back shortly."],
            default => ['title' => 'Something went wrong', 'description' => 'An unexpected error occurred. Please try again.'],
        };
    }
}
