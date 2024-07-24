<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jatelindo
    |--------------------------------------------------------------------------
    |
    | Data-data untuk layanan Multibilller
    | mitra PT Jasa Marga Tollroad Operation,
    | untuk metode Bahasa ISO 8583.
    |
    */
    'ip' => env('JPA_IP', '117.54.100.168'),
    'port' => env('JPA_PORT', '6502'),
    'url' => env('JPA_IP', '117.54.100.168').':'.env('JPA_PORT', '6502'),
    
    'bit_18' => env('JPA_BIT_18', '6012'),
    'bit_32' => env('JPA_BIT_32', '008'),
    'bit_41' => env('JPA_BIT_41', '54JMTO01'),
    'bit_42' => env('JPA_BIT_42', '200900100800000')
];
