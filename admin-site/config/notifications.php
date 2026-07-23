<?php

/*
 * SMS delivery settings.
 *
 * Set these as Apache/PHP environment variables rather than committing real
 * credentials to the project:
 *   TWILIO_ACCOUNT_SID
 *   TWILIO_AUTH_TOKEN
 *   TWILIO_FROM_NUMBER  (E.164 format, for example +15551234567)
 */
return [
    'twilio_account_sid' => trim((string) (getenv('TWILIO_ACCOUNT_SID') ?: '')),
    'twilio_auth_token' => trim((string) (getenv('TWILIO_AUTH_TOKEN') ?: '')),
    'twilio_from_number' => trim((string) (getenv('TWILIO_FROM_NUMBER') ?: '')),
];
