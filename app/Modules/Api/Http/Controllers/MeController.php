<?php

namespace App\Modules\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Models\PersonalAccessToken;
use DateTimeInterface;
use Illuminate\Http\Request;

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
