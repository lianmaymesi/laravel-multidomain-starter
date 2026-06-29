<?php

use App\Jobs\ExportUserData;
use App\Models\AccountDataExport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public $exports;

    public function mount(): void
    {
        $this->loadExports();
    }

    public function exportData(): void
    {
        ExportUserData::dispatch(Auth::user());
        session()->flash('exportQueued', true);
        $this->loadExports();
    }

    private function loadExports(): void
    {
        $this->exports = Auth::user()
            ->dataExports()
            ->latest()
            ->get();
    }
};
