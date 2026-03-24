<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    Auth::logout();

    session()->invalidate();
    session()->regenerateToken();

    $this->redirect(route('auth.login'), navigate: false);
};
?>

<div>
    {{-- Nothing in life is to be feared, it is only to be understood. Now is the time to understand more, so that we
    may fear less. - Maria Skłodowska-Curie --}}
</div>