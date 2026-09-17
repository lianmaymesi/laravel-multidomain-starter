<?php

use App\Jobs\ExportUserData;
use App\Models\AccountDataExport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public Collection $exports;

    public ?int $downloadExportId = null;
    public string $downloadPassword = '';

    public function mount(): void
    {
        $this->exports = new Collection;
        $this->loadExports();
    }

    public function exportData(): void
    {
        $export = Auth::user()->dataExports()->create([
            'token'  => Str::random(64),
            'status' => 'processing',
        ]);

        ExportUserData::dispatch($export);

        session()->flash('exportQueued', true);
        $this->loadExports();
    }

    public function initiateDownload(int $exportId): void
    {
        $this->downloadExportId = $exportId;
        $this->downloadPassword = '';
        $this->resetErrorBag('downloadPassword');
    }

    public function cancelDownload(): void
    {
        $this->downloadExportId = null;
        $this->downloadPassword = '';
        $this->resetErrorBag('downloadPassword');
    }

    public function confirmDownload(): void
    {
        $this->validate([
            'downloadPassword' => ['required', 'current_password'],
        ], [
            'downloadPassword.current_password' => __('Incorrect password.'),
        ]);

        $userId = Auth::id();
        $limiterKey = AccountDataExport::DOWNLOAD_LIMITER_KEY . $userId;

        if (RateLimiter::tooManyAttempts($limiterKey, AccountDataExport::MAX_DOWNLOADS_PER_DAY)) {
            $hours = (int) ceil(RateLimiter::availableIn($limiterKey) / 3600);
            $this->addError('downloadPassword', __('Download limit reached (3/day). Try again in :hours hour(s).', ['hours' => $hours]));
            return;
        }

        $export = Auth::user()->dataExports()->find($this->downloadExportId);

        if (! $export || ! $export->isReady()) {
            $this->cancelDownload();
            return;
        }

        $downloadToken = Str::random(32);

        $export->update([
            'download_token'            => $downloadToken,
            'download_token_expires_at' => now()->addSeconds(60),
        ]);

        RateLimiter::hit($limiterKey, 60 * 60 * 24);

        $url = route('account.export.download', ['token' => $export->token, 'dt' => $downloadToken]);

        // Record stats NOW so the table re-renders with correct data on this same response.
        // Controller only clears the one-time token after serving the file.
        $export->recordDownload();

        $this->reset('downloadExportId', 'downloadPassword');
        $this->loadExports();

        $this->js("
            const a = document.createElement('a');
            a.href = " . json_encode($url) . ";
            a.download = 'my-data-export.zip';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        ");
    }

    private function loadExports(): void
    {
        $this->exports = Auth::user()->dataExports()->latest()->get();
    }
};
