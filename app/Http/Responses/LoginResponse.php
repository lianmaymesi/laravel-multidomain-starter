<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return RedirectResponse|JsonResponse
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($this->redirectPath($request));
    }

    /**
     * Get the redirect path for the user.
     *
     * @param  Request  $request
     * @return string
     */
    protected function redirectPath(Request $request): string
    {
        if ($request->user()->privilege === 'staff') {
            return 'http://backoffice.justreadbible.test/dashboard';
        }

        return 'http://app.justreadbible.test/dashboard';
    }
}
