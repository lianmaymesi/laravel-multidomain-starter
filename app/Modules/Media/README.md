# Media module

File uploads for any model — storage, validation, image conversions, cleanup —
built on Laravel's own `Storage` disks plus [Intervention Image](https://image.intervention.io)
for resizing. No media-library package. Switch off with `MODULE_MEDIA=false`.

## Use it on a model

```php
use App\Modules\Media\Concerns\HasMedia;

class Invoice extends Model
{
    use HasMedia;
}
```

```php
$invoice->addMedia($request->file('scan'), 'attachment');   // validated, stored, converted
$invoice->getMedia('attachment');                           // originals, upload order
$user->getFirstMedia('avatar')?->conversion('thumb')->url(); // falls back to the original
$user->getFirstMediaUrl('avatar', 'thumb');                 // same, null if none
$invoice->clearMedia('attachment');                         // deletes rows + files
```

While the module is off every method is a no-op that behaves as if the model
has no media (`addMedia()` returns `null`), so core models like `User` can use
the trait safely. Data and files are kept, as for every module.

`User` already uses it — the **Profile photo** card on the account Settings
page is the module's own example.

## Upload component

```blade
<livewire:media::uploader :model="$invoice" collection="attachment" ability="update" />
```

Picking a file uploads it immediately with the collection's rules; each file
can be removed. `ability` is checked with `Gate::authorize($ability, $model)`
before every change — **leave it off only where the page itself guarantees the
viewer may edit that model** (like your own profile photo). A
`media-updated` event is dispatched after each change.

## Collections

Configured in [config.php](config.php) (`config('media.collections')`). A
collection not listed uses `default`.

```php
'avatar' => [
    'disk' => null,                                   // null = MEDIA_DISK
    'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_size' => 2048,                               // KB
    'dimensions' => ['min_width' => 100, 'min_height' => 100],
    'single' => true,                                 // a new upload replaces the old one
    'conversions' => [
        'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
        'medium' => ['width' => 600],                 // fit: 'contain' (default) — never upscales
    ],
],
```

Validation is server-side in `MediaService` on every upload; reuse
`MediaValidationService::rules($collection)` for your own form fields.

Conversions run synchronously on upload for `convertible_mimes` (JPEG, PNG,
GIF, WebP), keep the original format and disk, and are stored as their own
rows (`collection = "avatar:thumb"`, `conversion_of_id` → original). Other
files (PDFs, SVG, ...) are stored without conversions. Image driver:
`MEDIA_IMAGE_DRIVER=gd` (default) or `imagick` (needs the PHP extension).

## Storage

Files go to `{collection}/{model}/{id}/{uuid}.{ext}` on the resolved disk —
conversions in a `conversions/` folder next to it. Disk resolution: argument to
`addMedia()` → collection `disk` → `MEDIA_DISK` (default `local`).

`local` is private (`storage/app/private`); `Media::url()` returns a signed URL
that expires after `temporary_url_minutes`. Disks without temporary URLs
(e.g. `public`) get their plain URL.

### S3, Cloudflare R2, DigitalOcean Spaces (or MinIO, B2, ...)

One driver for all of them — Laravel's own S3 disk:

```bash
composer require league/flysystem-aws-s3-v3
```

```dotenv
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1          # R2: auto
AWS_BUCKET=my-bucket

# Cloudflare R2
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

# DigitalOcean Spaces
AWS_ENDPOINT=https://<region>.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Each media row stores its disk, so switching `MEDIA_DISK` later leaves old
files where they are; only new uploads go to the new disk.

## Cleanup

- Deleting a `Media` row deletes its file and its conversions.
- Deleting a model that uses `HasMedia` deletes its media (soft deletes wait
  for `forceDelete()`). Turn off with `delete_with_model => false`.
- Account deletion anonymizes the user instead of deleting it; the module
  listens for `App\Events\UserAnonymized` and removes that user's files.
- Nothing is deleted while the module is off.
