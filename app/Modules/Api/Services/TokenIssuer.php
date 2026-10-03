<?php

namespace App\Modules\Api\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Creates and revokes API tokens — the only place that does, so the policy
 * checks and the audit trail (activity log) can't be skipped.
 */
class TokenIssuer
{
    public function __construct(private ApiAccess $access) {}

    /**
     * A user creating their own token, within the access policy.
     *
     * @param  array<int, string>  $abilities
     *
     * @throws ValidationException
     */
    public function issueForSelf(User $user, string $name, array $abilities, ?int $days): NewAccessToken
    {
        if (! $this->access->canSelfServe($user)) {
            throw ValidationException::withMessages(['name' => __('Creating API tokens is not enabled for your account.')]);
        }

        if (array_diff($abilities, array_keys($this->access->selfServiceAbilities())) !== []) {
            throw ValidationException::withMessages(['abilities' => __('One or more abilities are not available to you.')]);
        }

        $max = $this->access->maxDays();

        if ($max !== null && ($days === null || $days > $max)) {
            throw ValidationException::withMessages(['expiresIn' => __('Tokens may last at most :days days.', ['days' => $max])]);
        }

        if ($user->tokens()->count() >= $this->access->maxTokens()) {
            throw ValidationException::withMessages(['name' => __('You already have the maximum of :count tokens — revoke one first.', ['count' => $this->access->maxTokens()])]);
        }

        return $this->create($user, $user, $name, $abilities, $days);
    }

    /**
     * An admin issuing a token to someone (an integration account, a
     * partner, …). Any ability, including full access ("*"), any lifetime —
     * the token still can't do more than its owner's permissions allow.
     *
     * @param  array<int, string>  $abilities
     *
     * @throws ValidationException
     */
    public function issueFor(User $owner, User $admin, string $name, array $abilities, ?int $days): NewAccessToken
    {
        $allowed = [...array_keys($this->access->abilities()), '*'];

        if (array_diff($abilities, $allowed) !== []) {
            throw ValidationException::withMessages(['issueAbilities' => __('Unknown ability.')]);
        }

        return $this->create($owner, $admin, $name, in_array('*', $abilities, true) ? ['*'] : $abilities, $days);
    }

    public function revoke(PersonalAccessToken $token, User $by): void
    {
        $owner = $token->tokenable;
        $token->delete();

        if ($owner instanceof User) {
            $this->log($by, $owner, 'api token revoked', ['token' => $token->name]);
        }
    }

    /** Emergency stop: every token, everyone's. Returns how many were revoked. */
    public function revokeAll(User $by): int
    {
        $count = PersonalAccessToken::query()->count();
        PersonalAccessToken::query()->delete();

        $this->log($by, $by, 'api tokens revoked', ['count' => $count, 'scope' => 'all']);

        return $count;
    }

    /** @param  array<int, string>  $abilities */
    private function create(User $owner, User $issuer, string $name, array $abilities, ?int $days): NewAccessToken
    {
        $token = $owner->createToken($name, array_values($abilities), $days === null ? null : now()->addDays($days));

        $token->accessToken->forceFill(['issued_by' => $issuer->getKey()])->save();

        $this->log($issuer, $owner, 'api token created', [
            'token' => $name,
            'abilities' => array_values($abilities),
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ]);

        return $token;
    }

    /** @param  array<string, mixed>  $properties */
    private function log(User $causer, User $subject, string $description, array $properties): void
    {
        activity('api')->causedBy($causer)->performedOn($subject)->withProperties($properties)->log($description);
    }
}
