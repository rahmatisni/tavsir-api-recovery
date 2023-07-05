<?php

namespace App\Services\External;

use App\Models\Constanta\PLNAREA;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class JatelindoService
{
    public static function inuqiry(PLNAREA $pln_area, string $id_pelanggan)
    {
        $date = Carbon::now();
        $md = $date->format('md');
        $his = $date->format('his');
        $payload = [
            'mti' => 200,
            "bit2" => $pln_area,
            "bit3" => "380000",
            "bit7" => $md.$his,
            "bit11" => $his,
            "bit12" => $his,
            "bit13" => $md,
            "bit15" => $md,
            "bit18" => config('jatelindo.bit_18'),
            "bit32" => config('jatelindo.bit_32'),
            "bit37" => "000000{$his}",
            "bit41" => config('jatelindo.bit_41'),
            "bit42" => config('jatelindo.bit_42'),
            "bit48" => $id_pelanggan,
            "bit49" => "360", // COUNTRY CURRENCY CODE NUMBER IDR 
        ];

        return Http::post(config('jatelindo.url'), $payload);
    }
}