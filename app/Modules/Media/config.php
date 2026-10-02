<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | Any disk from config/filesystems.php. "local" (private, served through
    | signed temporary URLs) needs nothing extra. For AWS S3, Cloudflare R2,
    | DigitalOcean Spaces or any S3-compatible store, run
    | `composer require league/flysystem-aws-s3-v3`, fill in the AWS_* keys
    | (plus AWS_ENDPOINT / AWS_USE_PATH_STYLE_ENDPOINT for R2/Spaces) and set
    | MEDIA_DISK=s3. Each media row remembers its disk, so switching later
    | leaves existing files where they are.
    |
    | Upload resolution order: disk passed to addMedia() → the collection's
    | 'disk' below → this default.
    |
    */

    'default_disk' => env('MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | "gd" (bundled with most PHP builds) or "imagick" (better resampling and
    | more formats, needs the imagick PHP extension).
    |
    */

    'image_driver' => env('MEDIA_IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Convertible Types
    |--------------------------------------------------------------------------
    |
    | Only these get conversions. Other images (SVG, HEIC, ...) and every
    | non-image file are stored as-is, no error.
    |
    */

    'convertible_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],

    /** Lifetime of signed URLs for disks that support them (local, S3). */
    'temporary_url_minutes' => 30,

    /** Delete a model's files when the model itself is (force) deleted. */
    'delete_with_model' => true,

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Per-collection rules. A collection not listed here uses 'default'.
    |
    |   disk         null = default_disk
    |   mimes        accepted MIME types (checked from file contents)
    |   max_size     kilobytes
    |   dimensions   min_width / min_height / max_width / max_height (images)
    |   single       true = a new upload replaces the previous one
    |   conversions  name => [width, height, fit: 'crop'|'contain']; stored as
    |                their own media rows, collection "<collection>:<name>"
    |
    */

    'collections' => [

        'default' => [
            'disk' => null,
            'mimes' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain', 'text/csv',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'max_size' => 10240,
        ],

        'avatar' => [
            'disk' => null,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => 2048,
            'dimensions' => ['min_width' => 100, 'min_height' => 100],
            'single' => true,
            'conversions' => [
                'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
                'medium' => ['width' => 600],
            ],
        ],

    ],

];
