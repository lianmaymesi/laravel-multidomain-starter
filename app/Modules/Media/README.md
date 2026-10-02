# Media module

A WordPress-style media library: every upload lands in one central library,
and models *use* library items through named collections. One image can be
used in many places; removing it from a model never deletes the file.
Built on Laravel's own `Storage` disks plus [Intervention Image](https://image.intervention.io)
for image sizes — no media-library package. Switch off with `MODULE_MEDIA=false`.

## The two screens

**Backoffice → Media Library** (`media.view`)

- **Add new** → drop zone: drag and drop (or pick) many files at once, with a
  progress bar. Each file is validated on its own — a bad one is reported
  without losing the rest.
- Grid with search, type filter (images / video / audio / documents) and
  *Load more*.
- Click a tile → details: preview (image, video or audio player), file info,
  editable **Title** and **Alt text**, **Copy URL**, **Used in** (every model
  and collection it's attached to), **Delete permanently**.
- **Bulk select** → tick tiles → **Delete selected**.
- Editing and deleting need `media.manage`, except for your own uploads.

**The media field** — anywhere a form needs files:

```blade
<livewire:media::field :model="$post" collection="gallery" ability="update" label="Gallery" />
```

Shows what's attached (with remove buttons) and an **Add media** button that
opens a picker with two tabs:

- **Upload files** — the same drop zone; new uploads are selected
  automatically and the picker switches to the library tab.
- **Media library** — search, filter, click to select (one or many, depending
  on the collection), **Insert**.

Only files that fit the collection (type, size, dimensions) are listed, and
only files the viewer may see: staff with `media.view` see the whole library,
everyone else only their own uploads.

The account **Settings → Profile photo** card is the module's own example
(`collection="avatar"`).

### Field: bound to a model, or a plain form input

```blade
{{-- Saves straight away. Pass `ability` (Gate::authorize($ability, $model))
     unless the page already guarantees the viewer may edit this model. --}}
<livewire:media::field :model="$post" collection="gallery" ability="update" />

{{-- Holds media ids in your component's $gallery; you save them yourself: --}}
<livewire:media::field wire:model="gallery" collection="gallery" />
```

```php
// In your save action — the actor check rejects ids this user may not see.
$post->syncMedia($this->gallery, 'gallery', auth()->user());
```

## In code

```php
use App\Modules\Media\Concerns\HasMedia;

class Post extends Model
{
    use HasMedia;
}
```

```php
$post->addMedia($request->file('cover'), 'cover');    // upload into the library + use it here
$post->attachMedia($media, 'gallery');                // use an existing library item
$post->syncMedia([4, 9, 2], 'gallery');               // exactly these, in this order
$post->detachMedia($media, 'gallery');                // stop using it (file stays)
$post->clearMedia('gallery');

$post->getMedia('gallery');                           // in order
$post->getFirstMediaUrl('cover', 'medium');           // a size, falls back to the original
$media->conversion('thumb')->url();
```

While the module is off every method is a no-op that behaves as if the model
has no media (`addMedia()` returns `null`), so core models like `User` can use
the trait safely.

Library-level work goes through `App\Modules\Media\Services\MediaLibrary`
(`upload()`, `attach()`, `sync()`, `detach()`).

## Configuration — [config.php](config.php)

| Key | |
|---|---|
| `library` | what may be uploaded at all: `mimes`, `max_size` (KB). SVG is excluded on purpose (it can carry scripts) |
| `collections` | per-collection narrowing: `mimes`, `max_size`, `dimensions`, `single`, `disk`. Unlisted collections use `default` |
| `conversions` | image sizes generated once per image, like WordPress: `thumb` 300² crop, `medium` 800, `large` 1600. Sizes the original already fits are skipped |
| `default_disk` / `MEDIA_DISK` | where files go (default `local`) |
| `image_driver` / `MEDIA_IMAGE_DRIVER` | `gd` or `imagick` |
| `per_page`, `temporary_url_minutes` | grid page size, signed URL lifetime |

Rules apply to new uploads *and* when an existing library item is picked.
Livewire's temporary-upload limit (12 MB by default) also caps uploads.

## Storage

Files go to `{Y}/{m}/{uuid}.{ext}` — sizes in a `conversions/` folder next to
them. Disk: argument → collection `disk` → `MEDIA_DISK`.

`local` is private (`storage/app/private`); `Media::url()` returns a signed URL
that expires after `temporary_url_minutes`. Disks without temporary URLs (e.g.
`public`) get their plain URL.

### S3, Cloudflare R2, DigitalOcean Spaces (or MinIO, B2, ...)

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

Each item stores its disk, so switching `MEDIA_DISK` later leaves old files
where they are.

## Deleting

| Action | Effect |
|---|---|
| Delete a library item | file + sizes deleted, removed from every model using it |
| Remove from a field / `detachMedia` / `clearMedia` | reference removed, file stays in the library |
| Delete a `HasMedia` model | its references removed (soft deletes wait for `forceDelete()`), files stay |
| Account deletion (`UserAnonymized`) | the user's references removed, and every file they uploaded that nothing else uses is deleted |
| Module off | nothing is deleted |

## Tables

- `media` — the library: `name` (title), `alt`, `original_name`, `mime_type`,
  `size`, `metadata` (width/height), `disk`, `path`, `uploaded_by`; image
  sizes are rows with `conversion_of_id` + `conversion`.
- `mediables` — uses: `media_id`, `mediable_type/id`, `collection`, `order`.
