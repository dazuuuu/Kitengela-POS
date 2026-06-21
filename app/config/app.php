<?php
// app/config/app.php
// Application-level settings only. All mail/SMTP settings live in app/config/mail.php.

return [
    'app_name'     => 'Modern POS',
    'app_url'      => 'http://localhost/Modern',   // change to your domain in production
    'debug'        => true,                          // set false in production
    'timezone'     => 'UTC',
    'session_name' => 'modern_session',

    // Security
    'hash_cost'    => 12,

    // OTP testing aid: when true, the login screen shows the 6-digit code and, if
    // sending failed, the exact reason. TESTING ONLY — set to false in production.
    'otp_debug'    => true,
];