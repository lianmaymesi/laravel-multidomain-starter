<?php

use App\Modules\Media\Concerns\HasMedia;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaValidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Generic uploader for any HasMedia model:
 *
 *   <livewire:media::uploader :model="$invoice" collection="attachment" ability="update" />
 *
 * Picking a file uploads it straight away (validated with the collection's
 * rules); each file can be removed again. $model and $collection are locked,
 * so the browser can't point the component at another record.
 *
 * Authorization: pass `ability` to have every upload/remove checked with
 * Gate::authorize($ability, $model). Without it, whoever can see the page the
 * component is on can change that model's media — only leave it off where
 * the page itself already guarantees that (e.g. your own profile photo).
 */
new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public Model $model;

    #[Locked]
    public string $collection = 'default';

    #[Locked]
    public ?string $ability = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $file = null;

    public function mount(Model $model, string $collection = 'default', ?string $ability = null): void
    {
        if (! in_array(HasMedia::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException($model::class.' must use '.HasMedia::class.' to be given to the media uploader.');
        }

        $this->model = $model;
        $this->collection = $collection;
        $this->ability = $ability;

        $this->authorizeChange();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Media> */
    public function items()
    {
        return $this->model->getMedia($this->collection);
    }

    public function single(): bool
    {
        return (bool) (app(MediaValidationService::class)->collection($this->collection)['single'] ?? false);
    }

    /** Human hint under the picker, e.g. "JPEG, PNG, WEBP · up to 2 MB". */
    public function hint(): string
    {
        $config = app(MediaValidationService::class)->collection($this->collection);

        $types = collect($config['mimes'] ?? [])
            ->map(fn (string $mime) => strtoupper(str($mime)->afterLast('/')->afterLast('.')->before('+')->toString()))
            ->unique()
            ->implode(', ');

        $size = isset($config['max_size']) ? __('up to :size', ['size' => Illuminate\Support\Number::fileSize($config['max_size'] * 1024)]) : '';

        return collect([$types, $size])->filter()->implode(' · ');
    }

    public function updatedFile(): void
    {
        $this->authorizeChange();

        $this->validate(['file' => app(MediaValidationService::class)->rules($this->collection)]);

        $this->model->addMedia($this->file, $this->collection);

        $this->reset('file');
        $this->dispatch('media-updated', collection: $this->collection);
    }

    public function remove(int $id): void
    {
        $this->authorizeChange();

        // Scoped to this model and collection, so an id from elsewhere is just a 404.
        $media = $this->model->media()->where('collection', $this->collection)->whereKey($id)->first();

        abort_if($media === null, 404);

        $media->delete();

        $this->dispatch('media-updated', collection: $this->collection);
    }

    private function authorizeChange(): void
    {
        if ($this->ability !== null) {
            Gate::authorize($this->ability, $this->model);
        }
    }
};
