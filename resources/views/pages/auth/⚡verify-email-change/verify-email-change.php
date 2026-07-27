<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $status = '';   // 'success' | 'invalid' | 'expired'
    public string $pendingEmail = '';

    public function mount(string $token): void
    {
        $user = User::where('pending_email_token', $token)->first();

        if (! $user || ! $user->pending_email || ! $user->pending_email_requested_at) {
            $this->status = 'invalid';
            return;
        }

        if ($user->pendingEmailExpired()) {
            $this->pendingEmail = $user->pending_email;
            $this->status       = 'expired';
            return;
        }

        $user->forceFill([
            'email'                      => $user->pending_email,
            'email_verified_at'          => now(),
            'pending_email'              => null,
            'pending_email_token'        => null,
            'pending_email_requested_at' => null,
        ])->save();

        $this->status = 'success';
    }
};
