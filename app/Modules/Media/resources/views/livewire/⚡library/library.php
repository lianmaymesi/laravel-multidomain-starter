<?php

use App\Modules\Media\Concerns\BrowsesLibrary;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Backoffice → Media Library. Drag-and-drop uploads, a searchable grid, and a
 * details panel (preview, title/alt, link, where it's used, delete). "Bulk
 * select" turns tile clicks into checkboxes for deleting several at once.
 *
 * media.view opens the page; editing or deleting needs media.manage, except
 * for your own uploads.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    use BrowsesLibrary;

    public bool $showUploader = false;

    public bool $bulk = false;

    /** @var array<int, int> */
    public array $checked = [];

    public ?int $activeId = null;

    public string $name = '';

    public string $alt = '';

    public ?string $status = null;

    private int $uploadedCount = 0;

    public function mount(): void
    {
        abort_unless(Gate::allows('media.view'), 403);
    }

    protected function rulesCollection(): ?string
    {
        return null;
    }

    protected function uploaded(Media $media): void
    {
        $this->uploadedCount++;
        $this->status = trans_choice(':count file uploaded.|:count files uploaded.', $this->uploadedCount);
    }

    public function select(int $id): void
    {
        $media = $this->find($id);

        $this->activeId = $media->id;
        $this->name = $media->name;
        $this->alt = (string) $media->alt;
        $this->resetErrorBag();
    }

    public function closeDetails(): void
    {
        $this->activeId = null;
    }

    public function active(): ?Media
    {
        return $this->activeId === null
            ? null
            : Media::query()->originals()->with(['conversions', 'uploader', 'attachments.mediable'])->find($this->activeId);
    }

    public function canEdit(Media $media): bool
    {
        return Gate::allows('media.manage') || ($media->uploaded_by !== null && $media->uploaded_by === auth()->id());
    }

    public function save(): void
    {
        $media = $this->find((int) $this->activeId);
        abort_unless($this->canEdit($media), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update(['name' => $this->name, 'alt' => $this->alt !== '' ? $this->alt : null]);

        $this->status = __('Saved.');
    }

    public function delete(int $id): void
    {
        $media = $this->find($id);
        abort_unless($this->canEdit($media), 403);

        $media->delete();

        if ($this->activeId === $id) {
            $this->activeId = null;
        }

        $this->status = __('":name" was deleted.', ['name' => $media->name]);
    }

    public function toggleBulk(): void
    {
        $this->bulk = ! $this->bulk;
        $this->checked = [];
    }

    public function toggleCheck(int $id): void
    {
        $this->checked = in_array($id, $this->checked, true)
            ? array_values(array_diff($this->checked, [$id]))
            : [...$this->checked, $id];
    }

    public function deleteChecked(): void
    {
        $deleted = 0;
        $skipped = 0;

        foreach (Media::query()->originals()->visibleTo(auth()->user())->whereKey($this->checked)->get() as $media) {
            if ($this->canEdit($media)) {
                $media->delete();
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $this->checked = [];
        $this->bulk = false;
        $this->activeId = null;

        $this->status = trans_choice(':count file deleted.|:count files deleted.', $deleted)
            .($skipped > 0 ? ' '.trans_choice(':count file skipped — you can only delete your own.|:count files skipped — you can only delete your own.', $skipped) : '');
    }

    private function find(int $id): Media
    {
        $media = Media::query()->originals()->visibleTo(auth()->user())->find($id);

        abort_if($media === null, 404);

        return $media;
    }
};
