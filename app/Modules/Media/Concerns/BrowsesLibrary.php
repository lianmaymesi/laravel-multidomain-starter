<?php

namespace App\Modules\Media\Concerns;

use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaLibrary;
use App\Modules\Media\Services\MediaValidationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Shared by the Media Library page and the media picker: searching,
 * filtering and paging the library, and drag-and-drop/multi-file uploads
 * into it. Each file is validated and stored on its own, so one bad file
 * doesn't sink the rest of a batch.
 */
trait BrowsesLibrary
{
    use WithFileUploads;

    public string $search = '';

    /** '', image, video, audio or document. */
    public string $type = '';

    public int $limit = 0;

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var array<int, string> per-file errors from the last upload batch */
    public array $uploadErrors = [];

    /** Collection whose rules apply to uploads and listing; null = library rules. */
    abstract protected function rulesCollection(): ?string;

    /** Called for every file stored by an upload batch. */
    protected function uploaded(Media $media): void {}

    public function mountBrowsesLibrary(): void
    {
        $this->limit = (int) config('media.per_page', 30);
    }

    public function updatedSearch(): void
    {
        $this->limit = (int) config('media.per_page', 30);
    }

    public function updatedType(): void
    {
        $this->limit = (int) config('media.per_page', 30);
    }

    public function loadMore(): void
    {
        $this->limit += (int) config('media.per_page', 30);
    }

    /** @return Collection<int, Media> one more than $limit, so the view can tell if there's more */
    public function libraryItems(): Collection
    {
        $mimes = $this->rulesCollection() === null ? [] : $this->libraryRules()['mimes'];

        return Media::query()
            ->originals()
            ->visibleTo(auth()->user())
            ->when($mimes !== [], fn ($query) => $query->whereIn('mime_type', $mimes))
            ->when($this->type !== '', fn ($query) => $query->ofType($this->type))
            ->when(trim($this->search) !== '', fn ($query) => $query->search(trim($this->search)))
            ->with('conversions')
            ->latest('id')
            ->take($this->limit + 1)
            ->get();
    }

    public function updatedUploads(): void
    {
        $library = app(MediaLibrary::class);
        $this->uploadErrors = [];

        foreach ($this->uploads as $file) {
            try {
                $this->uploaded($library->upload($file, auth()->user(), $this->rulesCollection()));
            } catch (ValidationException $e) {
                $this->uploadErrors[] = $file->getClientOriginalName().': '.collect($e->errors())->flatten()->first();
            }
        }

        $this->uploads = [];
    }

    /** "JPEG, PNG, WEBP · up to 2 MB", shown in the drop zone. */
    public function uploadHint(): string
    {
        $config = $this->libraryRules();

        $types = collect($config['mimes'] ?? [])
            ->map(fn (string $mime) => strtoupper(str($mime)->afterLast('/')->afterLast('.')->afterLast('-')->toString()))
            ->unique()
            ->take(8)
            ->implode(', ');

        $size = empty($config['max_size']) ? '' : __('up to :size', ['size' => Number::fileSize($config['max_size'] * 1024)]);

        return collect([$types, $size])->filter()->implode(' · ');
    }

    /** @return array<string, mixed> */
    protected function libraryRules(): array
    {
        return app(MediaValidationService::class)->collection($this->rulesCollection());
    }
}
