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


        $count_status_rekon = 0;
        $count_status_noRekon = 0;
        $count_remark = 0;
        $error_data = [];

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
                $status = 0;
                $rows = $row->toArray();
                $result = array_slice($rows, 1);
                if(strtolower($result[13]) !== 'ya') {
                    $count_status_rekon += 1;
                    $array_data = [
                        'refnum' => trim($result[3]),
                        'status_error' => 'Rekon Status Tidak Valid'
                    ];
                    array_push($error_data, $array_data);
                    $status = 1;

                }
                if(strtolower($result[14]) == '-') {
                    $count_status_noRekon += 1;
                    $array_data = [
                        'refnum' => $result[14],
                        'status_error' => 'Nomor Rekon Tidak Valid'
                    ];
                    array_push($error_data, $array_data);
                    $status = 2;
                }
                if(strtolower($result[14]) == '-') {
                    $count_remark += 1;
                    $array_data = [
                        'refnum' => $result[14],
                        'status_error' => 'Remark Tidak Valid'
                    ];
                    array_push($error_data, $array_data);
                    $status = 3;

                }
                // else {
                $data = ReportGetoll::updateOrCreate(
                    ['Tanggal_Transaksi' => $result[0]],
                    [
                        'Merchant' => $result[1],
                        'Sub_Merchant' => $result[2],
                        'Ref_Number' => trim($result[3]),
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
                        'Remark_Transaksi' => $result[17],
                        'Status_Report' => $this->getStatus($status) ?? 'Sukses'
                    ]
                );
                // }
            }
        }

        DB::commit();
        $hasil = [
            'status' => 'Berhasil',
            'count' => sizeof($collection),
            'error_count' => [
                'Not_Status_Rekon' => $count_status_rekon,
                'Not_Rekon' => $count_status_noRekon,
                'Not_Remark' => $count_status_noRekon
            ],
            'error_data' => $error_data
        ];
        $this->hasil = $hasil;
    }

    public function getStatus($status = null){
        if($status === 1) {
            return 'Rekon Status Tidak Valid';
        }
        if($status === 2) {
            return 'Nomor Rekon Tidak Valid';
        }   if($status === 3) {
            return 'Remark Disbursment Tidak Valid';
        }
        else {
            return 'Sukses';
        }
    }
    public function getHasil()
    {
        return $this->hasil;
    }
}
