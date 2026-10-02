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
    | MEDIA_DISK=s3. Each media item remembers its disk, so switching later
    | leaves existing files where they are.
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

    /** Lifetime of signed URLs for disks that support them (local, S3). */
    'temporary_url_minutes' => 30,

    /** Library items loaded per page / per "Load more". */
    'per_page' => 30,

    /*
    |--------------------------------------------------------------------------
    | Library Uploads
    |--------------------------------------------------------------------------
    |
    | What may be uploaded into the library at all (Media Library page, and
    | the "Upload files" tab of the picker unless a collection narrows it).
    | Types are checked from file contents. SVG is left out on purpose: it
    | can carry scripts. Livewire's own temporary-upload limit (12 MB by
    | default, config livewire.temporary_file_upload.rules) caps max_size.
    |
    */

    'library' => [
        'mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'video/mp4', 'video/webm', 'audio/mpeg', 'audio/wav',
        ],
        'max_size' => 10240, // KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | Generated once per uploaded image (like WordPress' thumbnail / medium /
    | large) and shared by every place the image is used. fit: 'crop' fills
    | the exact box, 'contain' (default) scales down within it, never up.
    | Only these types are resized; other files are stored as-is.
    |
    */

    'conversions' => [
        'thumb' => ['width' => 300, 'height' => 300, 'fit' => 'crop'],
        'medium' => ['width' => 800, 'height' => 800],
        'large' => ['width' => 1600, 'height' => 1600],
    ],

    'convertible_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Named slots a model attaches library items to ($user → "avatar",
    | $post → "gallery"). Each may narrow the library rules; anything not set
    | falls back to 'library' above. A collection not listed uses 'default'.
    | Rules are checked both for new uploads and when an existing library
    | item is picked.
    |
    |   mimes        accepted MIME types
    |   max_size     kilobytes
    |   dimensions   min_width / min_height / max_width / max_height (images)
    |   single       true = one item; picking another replaces it
    |   disk         where new uploads for this collection go (null = default)
    |
    */

    'collections' => [

        'default' => [],

        'avatar' => [
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'max_size' => 2048,
            'dimensions' => ['min_width' => 100, 'min_height' => 100],
            'single' => true,
        ],

    ],

];
