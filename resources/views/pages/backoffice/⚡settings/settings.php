<?php

use App\Models\LocaleSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    public string $urlMode = LocaleSetting::MODE_PATH;

    public function mount(): void
    {
        abort_unless(Gate::allows('languages.edit'), 403);

        $this->urlMode = LocaleSetting::urlMode();
    }

    public function save(): void
    {
        abort_unless(Gate::allows('languages.edit'), 403);

        $this->validate([
            'urlMode' => 'required|in:'.LocaleSetting::MODE_PATH.','.LocaleSetting::MODE_QUERY,
        ]);

        LocaleSetting::current()->update(['url_mode' => $this->urlMode]);

        session()->flash('status', 'Saved.');
    }
};
