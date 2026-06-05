<?php

// Easypaisa settings: real credentials .env mein rahengi, code mein hard-code nahi karni.
return [
    'mode' => env('EASYPAISA_MODE', 'sandbox'),
    'store_id' => env('EASYPAISA_STORE_ID', ''),
    'merchant_id' => env('EASYPAISA_MERCHANT_ID', ''),
    'hash_key' => env('EASYPAISA_HASH_KEY', ''),
    'checkout_url' => env('EASYPAISA_CHECKOUT_URL', ''),
    'return_url' => env('EASYPAISA_RETURN_URL', env('APP_URL', 'http://localhost').'/payments/easypaisa/return'),
    'webhook_secret' => env('EASYPAISA_WEBHOOK_SECRET', ''),
    'callback_window' => (int) env('EASYPAISA_CALLBACK_WINDOW', 300),
    'allow_local_paid_simulation' => filter_var(env('EASYPAISA_ALLOW_LOCAL_PAID_SIMULATION', in_array(env('APP_ENV'), ['local', 'testing'], true)), FILTER_VALIDATE_BOOLEAN),
];

