<?php

namespace App\Services\External;

use App\Models\Constanta\PLNAREA;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class JatelindoService
{
    public const inquiry = "380000";
    public const purchase = "171000";
    public const advice = "172000";
    public const repeat = "173000";

    public static function inquiry(string $id_pelanggan, ProductKiosBank $product)
    {
        $date = Carbon::now();
        $md = $date->format('md');
        $his = $date->format('his');
        $payload = [
            "mti" => "200",
            "bit2" => '053502',
            "bit3" => self::inquiry,
            "bit4" => $product->kode,
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
            "bit48" => $id_pelanggan,//trx id
            "bit49" => "360", // COUNTRY CURRENCY CODE NUMBER IDR 
        ];

        //fake respone
        if(false){
            $fake_payload = $payload;
            $fake_payload['mti'] = "210";
            $fake_payload['bit39'] = "00";
            $fake_payload['bit62'] = "5151106021222222 060000";
            Http::fake([
                config('jatelindo.url') => Http::response([
                    ...$fake_payload
                ], 200)
            ]);
        }

        return Http::withOptions()->post(config('jatelindo.url'), $payload);
    }
}