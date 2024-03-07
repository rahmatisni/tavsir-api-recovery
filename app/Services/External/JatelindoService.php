<?php

namespace App\Services\External;

use App\Models\Constanta\PLNAREA;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\LogKiosbank;
use App\Models\Product;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JatelindoService
{
    public const inquiry = "380000";
    public const purchase = "171000";
    public const advice = "172000";
    public const repeat = "173000";

    public static function inquiry(int $flag = 0, string $id_pelanggan, ProductKiosBank $product)
    {
        $date = Carbon::now();
        $md = $date->format('md');
        $his = $date->format('his');
        $id = str_pad($id_pelanggan, 11, '0', STR_PAD_LEFT).'000000000000'.'0';
        if($flag != 0){
            $id = '00000000000'.str_pad($id_pelanggan, 12, '0', STR_PAD_LEFT).'1';
        }
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
            "bit48" => 'JTL53L3'.$id,
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
        $result = Http::post(config('jatelindo.url'), $payload);
    
        Log::info([
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Inquiry',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function purchase(array $payload)
    {
        //bit
        if(isset($payload['bit39'])){
            unset($payload['bit39']);
        }
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
            // 'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);

        Log::info([
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Purchase',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function advice(array $payload)
    {
        //bit
        if(isset($payload['bit39'])){
            unset($payload['bit39']);
        }
        $payload ["mti"] = "0220";
        $payload ["bit3"] = self::advice;

        $result =  Http::withOptions([
            // 'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);

        Log::info([
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Advice',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function repeat(array $payload)
    {
        //bit
        if(isset($payload['bit39'])){
            unset($payload['bit39']);
        }
        $payload ["mti"] = "0221";
        $payload ["bit3"] = self::repeat;

        $result =  Http::withOptions([
            // 'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);

        Log::info([
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Repeate',
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
            
            case '34':
                $keterangan = 'Tagihan Sudah Lunas';
                $message = 'TRANSAKSI SUKSES';
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


    public static function infoPelanggan(LogKiosbank $log, string $trans_order_status)
    {
        $bit_48 = $log->data['bit48'] ?? '';
        $bit_61 = $log->data['bit61'] ?? ($log->data['bit62'] ?? '');
        $bit_62 = $log->data['bit62'] ?? '';

        $kode_distirbusi =  substr($bit_61, 0, 2);
        $unit_service =  substr($bit_61, 2, 5);
        $phone_unit_service =  substr($bit_61, 7, 15);
        $max_kwh =  ltrim(substr($bit_61, 22, 5),'0');
        $total_token_unsold =  substr($bit_61, 27, 1);
        $harga_token_unsold_1 =  substr($bit_61, 28, 11);
        $harga_token_unsold_2 =  substr($bit_61, 39, 11);

        $switcher_id = substr($bit_48 , 0, 7);
        $meter_id = substr($bit_48, 7, 11);
        $pelanggan_id = substr($bit_48, 18, 12);
        $flag = substr($bit_48, 30, 1);
        $transaksi_id = substr($bit_48, 31, 32);
        $ref_id = substr($bit_48, 63, 32);
        $vending_number = substr($bit_48, 95, 8);
        $nama_pelanggan = substr($bit_48, 103, 25);
        $tarif = substr($bit_48, 128, 4);
        $daya = substr($bit_48, 132, 9);
        $pilihan_pembelian =  substr($bit_48, 141, 1); //Generat token baru atau token unsold
        $digit_admin =  empty(substr($bit_48, 142, 1)) ? 0 : substr($bit_48, 142, 1); //Digit belakang koma
        $biaya_admin =  substr($bit_48, 143, 10); //Biaya Admin
        $digit_materai =  empty(substr($bit_48, 153, 1)) ? 0 : substr($bit_48, 153, 1); //Digit belakang koma
        $biaya_materai =  substr($bit_48, 154, 10); //Biaya Materai
        $digit_ppn =  empty(substr($bit_48, 164, 1)) ? 0 : substr($bit_48, 164, 1); //Digit belakang koma
        $biaya_ppn =  substr($bit_48, 165, 10); //Biaya PPN
        $digit_ppju =  empty(substr($bit_48, 175, 1)) ? 0 : substr($bit_48, 175, 1); //Digit belakang koma
        $biaya_ppju =  substr($bit_48, 176, 10); //Biaya PPJU
        $digit_angsuran =  empty(substr($bit_48, 186, 1)) ? 0 : substr($bit_48, 186, 1); //Digit belakang koma
        $biaya_angsuran =  substr($bit_48, 187, 10); //Biaya Angsuran
        $digit_pembelian =  empty(substr($bit_48, 197, 1)) ? 0 : substr($bit_48, 197, 1); //Digit belakang koma
        $biaya_pembelian =  substr($bit_48, 198, 12); //Biaya Pembelian listrik
        $digit_kwh =  empty(substr($bit_48, 210, 1)) ? 0 : substr($bit_48, 210, 1); //Digit belakang koma
        $biaya_kwh = (int) substr($bit_48, 211, 10); //Biaya Kwh
        $token =  substr($bit_48, 221, 20); //Token
        $tanggal_lunas =  substr($bit_48, 241, 14); //Tanggal lunas 

        if($trans_order_status == TransOrder::WAITING_PAYMENT){
            return [
                'Meter_ID' => substr($bit_48, 7, 11),
                'Pelanggan_ID' => substr($bit_48, 18, 12),
                'Flag' => substr($bit_48, 30, 1),
                'Transaksi_ID' => substr($bit_48, 31, 32),
                'Ref_ID' => substr($bit_48, 63, 32),
                'Nama_Pelanggan' => substr($bit_48, 95, 25),
                'Tarif' => substr($bit_48, 120, 4),
                'Daya' => ltrim(substr($bit_48, 124, 9),'0'),
                'Pilihan_Token' => substr($bit_48, 133, 1),
                'Total_Token_Unsold' => $total_token_unsold,
                'Token_Unsold_1' => number_format((int) $harga_token_unsold_1,0,',','.'),
                'Token_Unsold_2' => number_format((int) $harga_token_unsold_2,0,',','.'),
            ];
        }

        return [
            'Meter_ID' => $meter_id,
            'Pelanggan_ID' => $pelanggan_id,
            'Flag' => $flag,
            'Transaksi_ID' => $transaksi_id,
            'Ref_ID' => $ref_id,
            'Vending_Number' => $vending_number,
            'Nama_Pelanggan' => $nama_pelanggan,
            'Tarif' => $tarif,
            'Daya' => $daya,
            'Pilihan_Pembelian' => $pilihan_pembelian,
            'Biaya_Admin' => 'Rp. '.number_format( (int) substr($biaya_admin,0,-$digit_admin),0,',','.'),
            'Biaya_materai' => 'Rp. '.number_format((int) substr($biaya_materai,0,-$digit_materai),0,',','.'),
            'Biaya_ppn' => 'Rp. '.number_format((int) substr($biaya_ppn,0,-$digit_ppn),0,',','.'),
            'Biaya_ppju' => 'Rp. '.number_format((int) substr($biaya_ppju,0,-$digit_ppju),0,',','.'),
            'Biaya_angsuran' => 'Rp. '.number_format((int) substr($biaya_angsuran,0,-$digit_angsuran),0,',','.'),
            'Biaya_pembelian' => 'Rp. '.number_format((int) substr($biaya_pembelian,0,-$digit_pembelian),0,',','.'),
            'Jumlah_KWH' => number_format($biaya_kwh,0,',','.'),
            'Token' => wordwrap($token,4,' ', true),
            'Tanggal_Lunas' => Carbon::parse($tanggal_lunas)->format('Y-m-d H:i:s'),
            'Max_KWH' => $max_kwh,
            'Informasi' => $bit_62,
        ];
    }

    public static function infoPln(string $meter_id, int $flag = 0)
    {
        $date = Carbon::now();
        $md = $date->format('md');
        $his = $date->format('his');
        $id = str_pad($meter_id, 11, '0', STR_PAD_LEFT).'000000000000'.'0';
        if($flag != 0){
            $id = '00000000000'.str_pad($meter_id, 12, '0', STR_PAD_LEFT).'1';
        }
        $payload = [
            "mti" => "0200",
            "bit2" => '053502',
            "bit3" => self::inquiry,
            "bit4" => str_pad(0, 12, '0', STR_PAD_LEFT),
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
            "bit48" => 'JTL53L3'.$id,
            "bit49" => "360", // COUNTRY CURRENCY CODE NUMBER IDR 
        ];

        $header = [];
        if(app('env') != 'production'){
            $header = ['proxy' => '172.16.4.58:8090'];
        }
        $result = Http::withOptions($header)->post(config('jatelindo.url'), $payload);
    
        Log::info([
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Inquiry',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        if(($result['bit39'] ?? '') != '00'){
            return ['code' => 422, 'data' => JatelindoService::responseTranslation($result), 422];
        }

        $bit_48 = $result['bit48'];

        $info_user = [
            'Meter_ID' => substr($bit_48, 7, 11),
            'Pelanggan_ID' => substr($bit_48, 18, 12),
            'Flag' => substr($bit_48, 30, 1),
            // 'Transaksi_ID' => substr($bit_48, 31, 32),
            // 'Ref_ID' => substr($bit_48, 63, 32),
            'Nama_Pelanggan' => substr($bit_48, 95, 25),
            'Tarif' => substr($bit_48, 120, 4),
            'Daya' => ltrim(substr($bit_48, 124, 9),'0'),
        ];

        return [
            'info' => $info_user,
            'result_pln' => $result->json()
        ];
    }
}