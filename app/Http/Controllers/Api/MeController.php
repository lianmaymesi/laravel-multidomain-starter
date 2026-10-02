<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use DateTimeInterface;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class MeController extends Controller
{
    /** The user the token belongs to, plus the token's own name and abilities. */
    public function __invoke(Request $request): UserResource
    {
        $token = $request->user()->currentAccessToken();

        return (new UserResource($request->user()->load('roles')))->additional([
            'token' => $token instanceof PersonalAccessToken
                ? [
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'expires_at' => $token->expires_at instanceof DateTimeInterface ? $token->expires_at->format(DATE_ATOM) : null,
                ]
                : null,
        ]);
    }
}
