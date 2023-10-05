<?php

namespace App\Services\External;

use App\Models\Constanta\PLNAREA;
use App\Models\KiosBank\ProductKiosBank;
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
        $result = Http::withOptions([
            'proxy' => '172.16.4.58:8090'
        ])->post(config('jatelindo.url'), $payload);
    
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
            'status' => self::responseTranslation($result->json())?->keterangan,
            'action' => 'Purchase',
            'payload' => $payload,
            'respons' => $result->json(),
        ]);

        return $result;
    }

    public static function advice(array $payload)
    {
        $payload ["mti"] = "0220";
        $payload ["bit3"] = self::advice;

        $result =  Http::withOptions([
            'proxy' => '172.16.4.58:8090'
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
        $payload ["mti"] = "0221";
        $payload ["bit3"] = self::repeat;

        $result =  Http::withOptions([
            'proxy' => '172.16.4.58:8090'
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


    public static function infoPelanggan(string $bit_48, string $trans_order_status)
    {
        //example bit48 respon inquiry
        // $bit_48='JTL53L314234567895514444444444072A0D669D717D6E63AD027984D0147380007210ZD3233B973EE3BF82C6E7247BINDAH PUTRI              I3  000054321';
        if($trans_order_status == TransOrder::WAITING_PAYMENT){
            return [
                // 'switcher_id' => substr($bit_48 , 0, 7),
                'meter_id' => substr($bit_48, 7, 11),
                'pelanggan_id' => substr($bit_48, 18, 12),
                'flag' => substr($bit_48, 30, 1),
                'transaksi_id' => substr($bit_48, 31, 32),
                'ref_id' => substr($bit_48, 63, 32),
                'nama_pelanggan' => substr($bit_48, 95, 25),
                'tarif' => substr($bit_48, 120, 4),
                'daya' => substr($bit_48, 124, 9),
            ];
        }
        // JTL53L314234567895514444444444002637C4CB219F7E6BD35869C540406000007210Z10FF81DF3853FC1B7BC7ECD2INDAH PUTRI              I3  000054321

        // JTL53L3 Switcher ID 7
        // 14234567895 Meter ID 11
        // 514444444444 pelngggan ID 12
        // 0 falag 1
        // 02637C4CB219F7E6BD35869C54040600 Trx ID 32
        // 0007210Z10FF81DF3853FC1B7BC7ECD2 Ref ID 32
        // 00112233
        // INDAH PUTRI              
        // I3  
        // 000054321
        // 1  //Pembelian token baru = 0 / 1 token unsold
        // 2 //
        // 0000000000 //Biaya admin
        // 2 //
        // 0000000000 //biaya materai
        // 2
        // 0000020408 // PPN Rp. 204,08
        // 2
        // 0000019996 // PPJU
        // 2
        // 0000019794 // Angsuran
        // 2
        // 000001939802 // Nial Minor Pembelian Listrik
        // 2
        // 0000001621 // jumlah Kwh
        // 23464176114234567895 // token
        // 20230926161426 //tanggal lunas
        
        //example bit48 respon payment
        // $bit_48 = 'JTL53L314234567895514444444444072A0D669D717D6E63AD027984D0147380007210ZD3233B973EE3BF82C6E7247B00112233INDAH PUTRI              I3  000054321120000000000200000000002000002040820000019996200000197942000001939802200000016212349404851423456789520230920140953';
        
            // 'bit_48' => $bit_48,
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
        return [
            'meter_id' => $meter_id,
            'pelanggan_id' => $pelanggan_id,
            'flag' => $flag,
            'transaksi_id' => $transaksi_id,
            'ref_id' => $ref_id,
            'vending_number' => $vending_number,
            'nama_pelanggan' => $nama_pelanggan,
            'tarif' => $tarif,
            'daya' => $daya,
            'pilihan_pembelian' => $pilihan_pembelian,
            'biaya_admin' => 'Rp. '.number_format( (int) substr($biaya_admin,0,-$digit_admin),0,',','.'),
            'biaya_materai' => 'Rp. '.number_format((int) substr($biaya_materai,0,-$digit_materai),0,',','.'),
            'biaya_ppn' => 'Rp. '.number_format((int) substr($biaya_ppn,0,-$digit_ppn),0,',','.'),
            'biaya_ppju' => 'Rp. '.number_format((int) substr($biaya_ppju,0,-$digit_ppju),0,',','.'),
            'biaya_angsuran' => 'Rp. '.number_format((int) substr($biaya_angsuran,0,-$digit_angsuran),0,',','.'),
            'biaya_pembelian' => 'Rp. '.number_format((int) substr($biaya_pembelian,0,-$digit_pembelian),0,',','.'),
            'jumlah_kwh' => number_format($biaya_kwh,0,',','.'),
            'token' => wordwrap($token,4,' ', true),
            'tanggal_lunas' => Carbon::parse($tanggal_lunas)->format('Y-m-d H:i:s'),
        ];
    }

}