<?php

namespace App\Modules\Api\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Sanctum's token plus who issued it: the owner (self-service) or an admin.
 * Registered with Sanctum while the API module is on.
 *
 * @property int|null $issued_by
 */
class PersonalAccessToken extends SanctumToken
{
    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
