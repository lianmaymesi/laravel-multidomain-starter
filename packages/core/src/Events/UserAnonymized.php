<?php

namespace Atrium\Core\Events;

use Atrium\Core\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once a deleted account's personal data has been anonymized. Modules
 * that keep personal data of their own (uploaded photos, ...) listen for it
 * and remove theirs — core never has to know about them.
 */
class UserAnonymized
{
    use Dispatchable;

    public function __construct(public User $user) {}
}
