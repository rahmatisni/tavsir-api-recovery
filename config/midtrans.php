<?php

return [
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'server_key' => env('MIDTRANS_SERVER_KEY', 'your-server-key'),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', false),
    'is_3ds' => env('MIDTRANS_IS_3DS', false),
];