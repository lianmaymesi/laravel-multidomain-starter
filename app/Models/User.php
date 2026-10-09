<?php

namespace App\Models;

use App\Modules\Media\Concerns\HasMedia;
use Atrium\Core\Models\User as AtriumUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

/**
 * This project's user. Everything Atrium needs lives in the parent class —
 * add your own columns, relations and casts here. Registered with
 * Platform::useUserModel() in AppServiceProvider.
 */
class User extends AtriumUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasMedia;

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'trial_ends_at' => 'datetime',
        ];
    }

    protected function anonymizeMap(): array
    {
        return [
            ...parent::anonymizeMap(),
            // Billing (Cashier) columns: card brand/last four are personal.
            // stripe_id stays — invoices and refunds still hang off it.
            'pm_type' => null,
            'pm_last_four' => null,
        ];
    }
}
