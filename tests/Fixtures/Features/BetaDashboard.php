<?php

namespace Tests\Fixtures\Features;

use Atrium\Core\Models\User;
use Atrium\Core\Support\Features\UserFlag;
use Illuminate\Support\Lottery;

/** Per-user flag with a percentage rollout, defined by tests only. */
class BetaDashboard extends UserFlag
{
    public string $name = 'beta-dashboard';

    public function label(): string
    {
        return 'Beta dashboard';
    }

    protected function initial(?User $user): mixed
    {
        return $user === null ? false : Lottery::odds(1, 10);
    }
}
