<?php

use App\Modules\Media\Concerns\BrowsesLibrary;
use App\Modules\Media\Concerns\HasMedia;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaValidationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * WordPress-style media field: shows what's attached, and "Add media" opens
 * a picker with two tabs — Upload files (drag and drop) and Media library
 * (pick existing). New uploads are selected automatically; Insert attaches.
 *
 * Bound to a model — changes are saved straight away:
 *   <livewire:media::field :model="$post" collection="gallery" ability="update" />
 *
 * Or as a plain form input holding media ids — you save them yourself,
 * e.g. $post->syncMedia($this->gallery, 'gallery', auth()->user()):
 *   <livewire:media::field wire:model="gallery" collection="gallery" />
 *
 * The picker only lists files the viewer may see (Media::visibleTo). With a
 * model, pass `ability` to check Gate::authorize($ability, $model) before
 * every change — leave it off only where the page already guarantees it.
 */
new class extends Component
{
    use BrowsesLibrary;

    #[Locked]
    public ?Model $model = null;

    #[Locked]
    public string $collection = 'default';

    #[Locked]
    public ?string $ability = null;

    /** @var array<int, int> media ids, when used without a model */
    #[Modelable]
    public array $value = [];

    public string $label = '';

    /** "upload" or "library" */
    public string $tab = 'library';

    /** @var array<int, int> picked in the open picker, not yet inserted */
    public array $selected = [];

    public function mount(?Model $model = null, string $collection = 'default', ?string $ability = null, string $label = ''): void
    {
        if ($model !== null && ! in_array(HasMedia::class, class_uses_recursive($model), true)) {
            throw new InvalidArgumentException($model::class.' must use '.HasMedia::class.' to be given to the media field.');
        }

        $this->model = $model;
        $this->collection = $collection;
        $this->ability = $ability;
        $this->label = $label;

        $this->authorizeChange();
    }

    protected function rulesCollection(): ?string
    {
        return $this->collection;
    }

    protected function uploaded(Media $media): void
    {
        $this->selected = $this->single() ? [$media->id] : [...$this->selected, $media->id];
        $this->tab = 'library';
    }

    public function single(): bool
    {
        return (bool) (app(MediaValidationService::class)->collection($this->collection)['single'] ?? false);
    }

    public function modalName(): string
    {
        return 'media-picker-'.$this->getId();
    }

    /** @return Collection<int, Media> */
    public function items(): Collection
    {
        if ($this->model !== null) {
            return $this->model->getMedia($this->collection);
        }

        // Ids come from the browser: only ever render files this viewer may see.
        $order = array_flip(array_map('intval', $this->value));

        return Media::query()->originals()->visibleTo(auth()->user())->whereKey($this->value)->with('conversions')->get()
            ->sortBy(fn (Media $media) => $order[$media->id] ?? PHP_INT_MAX)
            ->values();
    }

    public function openPicker(): void
    {
        $this->authorizeChange();

        $this->selected = [];
        $this->uploadErrors = [];
        $this->resetErrorBag();
        $this->tab = $this->libraryItems()->isEmpty() ? 'upload' : 'library';

        $this->modal($this->modalName())->show();
    }

    public function toggle(int $id): void
    {
        if ($this->single()) {
            $this->selected = $this->selected === [$id] ? [] : [$id];

            return;
        }

        $this->selected = in_array($id, $this->selected, true)
            ? array_values(array_diff($this->selected, [$id]))
            : [...$this->selected, $id];
    }

    public function insert(): void
    {
        $this->authorizeChange();

        $media = Media::query()->originals()->visibleTo(auth()->user())->whereKey($this->selected)->get()->keyBy('id');

        // Anything not visible to this viewer can't be attached, whatever the browser sent.
        $ids = array_values(array_filter(array_map('intval', $this->selected), fn (int $id) => $media->has($id)));

        foreach ($ids as $id) {
            if (($message = app(MediaValidationService::class)->rejectionFor($media[$id], $this->collection)) !== null) {
                $this->addError('selected', $message);

                return;
            }
        }

        if ($ids === []) {
            return;
        }

        if ($this->model !== null) {
            foreach ($ids as $id) {
                $this->model->attachMedia($media[$id], $this->collection);
            }
        } else {
            $this->value = $this->single() ? [$ids[0]] : array_values(array_unique([...array_map('intval', $this->value), ...$ids]));
        }

        $this->selected = [];
        $this->modal($this->modalName())->close();
        $this->dispatch('media-updated', collection: $this->collection);
    }

    public function remove(int $id): void
    {
        $this->authorizeChange();

        if ($this->model !== null) {
            $this->model->detachMedia($id, $this->collection);
        } else {
            $this->value = array_values(array_diff(array_map('intval', $this->value), [$id]));
        }

        $this->dispatch('media-updated', collection: $this->collection);
    }

    private function authorizeChange(): void
    {
        if ($this->model !== null && $this->ability !== null) {
            Gate::authorize($this->ability, $this->model);
        }
    }
};
