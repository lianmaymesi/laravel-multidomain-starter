<?php

namespace App\Enums;

enum OtpType: string
{
    case PHONE_VERIFICATION = 'phone_verification';
    case EMAIL_VERIFICATION = 'email_verification';
    case PHONE_LOGIN = 'phone_login';
    case EMAIL_FORGOT_PASSWORD = 'email_forgot_password';
    case PHONE_FORGOT_PASSWORD = 'phone_forgot_password';
    case PASSWORD_RESET = 'password_reset';
}
