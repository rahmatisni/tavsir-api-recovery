<?php

namespace App\Services\External;

use App\Models\Constanta\PLNAREA;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            "mti" => "0200",
            "bit2" => '053502',
            "bit3" => self::inquiry,
            "bit4" => str_pad($product->kode, 12, '0', STR_PAD_LEFT),
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
            //Format SwitcherID + MeterID (11 digit) + PelangganID (12 digit) + Flag MeterID (0) atau PelangganID (1)  
            "bit48" => 'JTL53L3'.str_pad($id_pelanggan, 11, '0', STR_PAD_LEFT).'000000000000'.'0',
            "bit49" => "360", // COUNTRY CURRENCY CODE NUMBER IDR 
        ];

        //fake respone
        if(false){
            $fake_payload = $payload;
            $fake_payload['mti'] = "0210";
            $fake_payload['bit39'] = "00";
            $fake_payload['bit62'] = "5151106021222222 060000";
            Http::fake([
                config('jatelindo.url') => Http::response([
                    ...$fake_payload
                ], 200)
            ]);
        }
        $result = Http::withOptions([
            'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);

        Log::info([
            'status' => self::responseTranslation($result->json()['bit39'] ?? '')?->keterangan,
            'action' => 'Inquiry',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function purchase(array $payload)
    {
        $payload ["mti"] = "0200";
        $payload ["bit3"] = self::purchase;

        //fake respone
        if(false){
            $fake_payload = $payload;
            $fake_payload['mti'] = "210";
            $fake_payload['bit39'] = "00";
            $fake_payload['bit62'] = "Token \"TMP\" : 9542 6732 9878 2346 1252 Kompensasi   5 KWh";
            $fake_payload['bit61'] = "5151106021222222 060000";
            Http::fake([
                config('jatelindo.url') => Http::response([
                    ...$fake_payload
                ], 200)
            ]);
        }

        $result =  Http::withOptions([
            'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);

        Log::info([
            'status' => self::responseTranslation($result->json()['bit39'] ?? '')?->keterangan,
            'action' => 'Purchase',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function responseTranslation($response){
        $keterangan = '';
        $message = '';
        $bit39 = $response['bit39'] ?? '';
        switch ($bit39) {
            case '00':
                $keterangan = 'Approved';
                $message = 'TRANSAKSI SUKSES';
                break;
            
            case '06':
                $keterangan = 'Error lainnya';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '09':
                $keterangan = 'NOMOR METER/IDPEL YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                $message = 'NOMOR METER/IDPEL YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                break;
            
            case '13':
                $keterangan = 'Application Server is Down';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '14':
                $keterangan = 'NOMOR METER/IDPEL YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                $message = 'NOMOR METER/IDPEL YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                break;
            
            case '16':
                $keterangan = 'KONSUMEN DIBLOKIR HUBUNGI PLN';
                $message = 'KONSUMEN DIBLOKIR HUBUNGI PLN';
                break;
            
            case '17':
                $keterangan = 'NOMINAL PEMBELIAN TIDAK TERDAFTAR';
                $message = 'NOMINAL PEMBELIAN TIDAK TERDAFTAR';
                break;
            
            case '18':
                $keterangan = 'Request Timeout';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '31':
                $keterangan = 'Id Bank masih belum didaftarkan';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '32':
                $keterangan = 'Switcher Id belum terdaftar';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '33':
                $keterangan = 'Produk belum terdaftar';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '47':
                $keterangan = 'TOTAL KWH MELEBIHI BATAS MAKSIMUM';
                $message = 'TOTAL KWH MELEBIHI BATAS MAKSIMUM';
                break;
            
            case '61':
                $keterangan = 'SALDO ANDA TIDAK MENCUKUPI';
                $message = 'SALDO ANDA TIDAK MENCUKUPI';
                break;

            case '73':
                $keterangan = 'Kode produk belum terdaftar di TID, silahkan hubungi tim Jatelindo';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '75':
                $keterangan = 'PEMBELIAN MINIMAL RP. 20 RIBU';
                $message = 'PEMBELIAN MINIMAL RP. 20 RIBU';
                break;
            
            case '77':
                $keterangan = 'NOMOR METER YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                $message = 'NOMOR METER YANG ANDA MASUKKAN SALAH, MOHON TELITI KEMBALI';
                break;
            
            case '90':
                $keterangan = 'Transaksi tidak bisa dilakukan karena cut off sedang dalam progress';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '96':
                $keterangan = 'Advice tidak berhasil,tidak ada purchase';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            case '98':
                $keterangan = 'Error, PLN reference number tidak sesuai dengan PLN Refnum saat Inquery';
                $message = 'TRANSAKSI GAGAL';
                break;
            
            default:
                $keterangan = 'Code Undefined';
                $message = 'Code Undefined';
                break;
        }

        return (object) array('kode' => $bit39, 'keterangan' => $keterangan, 'message' => $message);
    }
}