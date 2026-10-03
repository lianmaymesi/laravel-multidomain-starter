<?php

use App\Modules\Language\Models\Language;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    #[Validate('required|string|size:2|alpha')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $native_name = '';

    #[Validate('required|in:ltr,rtl')]
    public string $direction = 'ltr';

    public function mount(): void
    {
        abort_unless(Gate::allows('languages.view'), 403);
    }

    public function languages()
    {
        return Language::orderBy('order')->get();
    }

    public function add(): void
    {
        abort_unless(Gate::allows('languages.create'), 403);

        $data = $this->validate();
        $data['code'] = strtolower($data['code']);

        if (Language::where('code', $data['code'])->exists()) {
            $this->addError('code', __('That language code is already added.'));

            return;
        }

        $data['order'] = (int) Language::max('order') + 1;
        $data['is_primary'] = ! Language::query()->exists();

        Language::create($data);

        $this->reset(['code', 'name', 'native_name']);
        $this->direction = 'ltr';

        session()->flash('status', __('Language added.'));
    }

    public function toggleActive(int $id): void
    {
        abort_unless(Gate::allows('languages.edit'), 403);

        $language = Language::findOrFail($id);

        if ($language->is_primary && $language->is_active) {
            session()->flash('error', __('The primary language must stay active — set another language as primary first.'));

            return;
        }

        $language->update(['is_active' => ! $language->is_active]);
    }

    public function makePrimary(int $id): void
    {
        abort_unless(Gate::allows('languages.edit'), 403);

        $language = Language::findOrFail($id);
        $language->update(['is_primary' => true, 'is_active' => true]);

        session()->flash('status', __('":name" is now the primary language.', ['name' => $language->name]));
    }

    public function move(int $id, int $direction): void
    {
        abort_unless(Gate::allows('languages.edit'), 403);

        $languages = $this->languages();
        $index = $languages->search(fn (Language $language) => $language->id === $id);

        $swapWith = $languages->get($index + $direction);
        if ($swapWith === null) {
            return;
        }

        $language = $languages->get($index);
        [$a, $b] = [$language->order, $swapWith->order];
        $language->update(['order' => $b]);
        $swapWith->update(['order' => $a]);
    }

    public function delete(int $id): void
    {
        abort_unless(Gate::allows('languages.delete'), 403);

        $language = Language::findOrFail($id);

        if ($language->is_primary) {
            session()->flash('error', __('The primary language can\'t be deleted — set another language as primary first.'));

            return;
        }

        $language->delete();

        session()->flash('status', __('Language removed.'));
    }
};
