<?php

namespace App\Modules\Api\Services;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\Modules\Module;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The API access policy, set by a Super Admin on Backoffice → API Access and
 * stored as one AppSetting (defaults in config('api.policy')). It decides
 * who may create their *own* tokens and within what limits. Admins can
 * always issue tokens to anyone.
 */
class ApiAccess
{
    public const SETTING = 'api_access_policy';

    public const MODES = ['none', 'staff', 'everyone', 'roles'];

    /**
     * @return array{self_service: string, roles: array<int, string>, abilities: array<int, string>, max_days: ?int, max_tokens: int}
     */
    public function policy(): array
    {
        $stored = json_decode((string) AppSetting::get(self::SETTING, ''), true);

        return array_merge(config('api.policy'), is_array($stored) ? $stored : []);
    }

    /** @param  array{self_service: string, roles: array<int, string>, abilities: array<int, string>, max_days: ?int, max_tokens: int}  $policy */
    public function savePolicy(array $policy): void
    {
        AppSetting::set(self::SETTING, json_encode($policy));
    }

    /**
     * Every ability a token can hold: core's plus those enabled modules
     * contribute — ability => description.
     *
     * @return array<string, string>
     */
    public function abilities(): array
    {
        $abilities = config('api.abilities', []);

        foreach (Module::contributions('api.abilities') as $item) {
            $abilities[$item['ability']] = $item['description'];
        }

        ksort($abilities);

        return $abilities;
    }

    /** @return array<string, string> abilities self-service tokens may have */
    public function selfServiceAbilities(): array
    {
        return array_intersect_key($this->abilities(), array_flip($this->policy()['abilities']));
    }

    public function canSelfServe(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $policy = $this->policy();

        return match ($policy['self_service']) {
            'everyone' => true,
            'staff' => $user->isStaff(),
            'roles' => $user->roles->pluck('slug')->intersect($policy['roles'])->isNotEmpty(),
            default => false,
        };
    }

    public function maxDays(): ?int
    {
        $days = $this->policy()['max_days'];

        return $days === null ? null : (int) $days;
    }

    public function maxTokens(): int
    {
        return max(1, (int) $this->policy()['max_tokens']);
    }

    /** A token the user created for themselves (not one an admin issued them). */
    public function isSelfIssued(PersonalAccessToken $token): bool
    {
        return $token->issued_by === null || (int) $token->issued_by === (int) $token->tokenable_id;
    }

    /**
     * Whether a token may be used right now. Admin-issued tokens always may;
     * self-issued ones only while their owner may still self-serve — so
     * tightening the policy takes effect immediately, not when tokens expire.
     */
    public function allows(User $user, PersonalAccessToken $token): bool
    {
        return ! $this->isSelfIssued($token) || $this->canSelfServe($user);
    }
}
