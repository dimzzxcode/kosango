<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aturan bisnis booking
    |--------------------------------------------------------------------------
    |
    | Seluruh angka bisnis dibaca lewat App\Services\BookingSettings sehingga
    | tidak ada nilai ajaib yang tersebar di dalam aplikasi.
    |
    */

    'min_down_payment_percent' => 50,

    'late_grace_days' => 7,

    'late_fee' => 50_000,

    'refund_window_days' => 3,

    'payment_expiry_hours' => 24,

    'max_rejected_uploads' => 3,

    'extension_open_days_before_end' => 14,

    'max_gallery_images' => 10,

    'terms_version' => 1,

    'invoice_reminder_days' => 3,

    'default_max_duration' => [
        'daily' => 30,
        'monthly' => 24,
        'yearly' => 5,
    ],

    'payment_driver' => env('PAYMENT_DRIVER', 'manual'),

    'payment_webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),

];
