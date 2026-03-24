<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    /**
     * Generate a new secret for the user, store it (unconfirmed).
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->engine->generateSecretKey(32);

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled_at' => false,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /**
     * Return QR code SVG markup for the setup page.
     */
    public function qrCodeSvg(User $user): string
    {
        $secret = decrypt($user->two_factor_secret);

        $otpAuthUrl = $this->engine->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($otpAuthUrl);
    }

    /**
     * Validate the 6-digit code and confirm 2FA setup.
     */
    public function confirm(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);

        $valid = $this->engine->verifyKey($secret, $code);

        if ($valid) {
            $user->forceFill([
                'two_factor_enabled_at' => now(),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(
                    json_encode($this->generateRecoveryCodes())
                ),
            ])->save();
        }

        return $valid;
    }

    /**
     * Validate a TOTP code during login challenge.
     */
    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);

        // Window of 1 = accepts ±30s drift
        return (bool) $this->engine->verifyKey($secret, $code, 1);
    }

    /**
     * Validate a backup recovery code and consume it.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->twoFactorRecoveryCodes();

        $index = array_search(trim($code), $codes, true);

        if ($index === false) {
            return false;
        }

        // Remove the used code
        array_splice($codes, $index, 1);

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        return true;
    }

    /**
     * Disable 2FA completely for a user.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * To generate recovery codes
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn () => Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5)),
            range(1, $count)
        );
    }
}
