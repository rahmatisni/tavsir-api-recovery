<?php

namespace App\Imports;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransStock;
use App\Models\ReportGetoll;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReportImportGetoll implements ToCollection
{
    private $hasil;
    private $type;

    function __construct($type)
    {
        $this->type = $type;
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {

        DB::beginTransaction();
        foreach ($collection as $key => $row) {
            if ($key == 0) {
                $key_header = [
                    'No',
                    'Tanggal Transaksi',
                    'Merchant',
                    'Sub Merchant',
                    'Reff Number',
                    'Kode Metode Pembayaran',
                    'Source of Fund',
                    'Nominal',
                    'Status Transaksi',
                    'Tanggal Settle',
                    'PG Fee',
                    'Merchant Fee',
                    'Sub Merchant Fee',
                    'SOF Fee',
                    'Status Rekon',
                    'Nomor Rekon',
                    'Tanggal Rekon',
                    'Remark Disbursement',
                    'Remark Transaksi'
                ];
                if ($row->toArray() != $key_header) {
                    throw new Exception('Format Excel tidak sesuai');
                    // $this->hasil = [
                    //     'Status' => 'Gagal',
                    //     'Message' => 'Format Excel tidak sesuai'
                    // ];
                }
            } else {
                $rows = $row->toArray();
                $result = array_slice($rows, 1);
                $data = ReportGetoll::updateOrCreate(
                    ['Tanggal_Transaksi' => $result[0]],
                    [
                        'Merchant' => $result[1],
                        'Sub_Merchant' => $result[2],
                        'Ref_Number' => $result[3],
                        'Kode_Metode_Pembayaran' => $result[4],
                        'Source_of_Fund' => $result[5],
                        'Nominal' => $result[6],
                        'Status_Transaksi' => $result[7],
                        'Tanggal_Settle' => $result[8],
                        'PG_Fee' => $result[9],
                        'Merchant_Fee' => $result[10],
                        'Sub_Merchant_Fee' => $result[11],
                        'SOF_Fee' => $result[12],
                        'Status_Rekon' => $result[13],
                        'Nomor_Rekon' => $result[14],
                        'Tanggal_Rekon' => $result[15],
                        'Remark_Disbursement' => $result[16],
                        'Remark_Transaksi' => $result[17]
                    ]
                );
            }
        }

        DB::commit();
        $hasil = [
            'status' => 'Berhasil',
            'count' => sizeof($collection),
        ];
        $this->hasil = $hasil;
    }

    public function getHasil()
    {
        return $this->hasil;
    }
}
