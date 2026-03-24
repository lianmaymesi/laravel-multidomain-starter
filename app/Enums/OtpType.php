<?php

namespace App\Enums;

enum OtpType: string
{
    case PHONE_VERIFICATION = 'phone_verification';
    case EMAIL_VERIFICATION = 'email_verification';
    case PHONE_LOGIN = 'phone_login';
    case PASSWORD_RESET = 'password_reset';
}
