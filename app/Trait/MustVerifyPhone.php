<?php

namespace App\Trait;

trait MustVerifyPhone
{
    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhone()
    {
        if (! config('multidomain.phone_verification_enabled')) {
            return true;
        }

        return ! is_null($this->phone_verified_at);
    }

    /**
     * Mark the user's phone as verified.
     *
     * @return bool
     */
    public function markPhoneAsVerified()
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Mark the user's phone as unverified.
     *
     * @return bool
     */
    public function markPhoneAsUnverified()
    {
        return $this->forceFill([
            'phone_verified_at' => null,
        ])->save();
    }

    /**
     * Send the phone verification notification.
     *
     * @return void
     */
    public function sendPhoneVerificationNotification()
    {
        // $this->notify(new VerifyPhone);
    }

    /**
     * Get the phone number that should be used for verification.
     *
     * @return string
     */
    public function getPhoneForVerification()
    {
        return $this->phone;
    }
}
