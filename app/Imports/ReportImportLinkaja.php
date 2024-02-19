<?php

namespace App\Imports;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\ReportLink;
use App\Models\TransStock;
use App\Models\ReportGetoll;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReportImportLinkaja implements ToCollection
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
                    '',
                    'Top Org Name',
                    'Top Short Code',
                    'Biz Org Name',
                    'Short Code',
                    'Orderid',
                    'Linkedorderid',
                    'Linkedorder Create Time',
                    'Linkedorder End Time',
                    'Invoice ID',
                    'Trans End Time',
                    'Trans Initiate Time',
                    'Transaction Type',
                    'Transaction Scenario',
                    'Trans Status',
                    'Gateway',
                    'Tid',
                    'Trx ID',
                    'Transaction Reference Number',
                    'Bill Ref Number',
                    'Note',
                    'Recharged Msisdn',
                    'Partner Trx ID',
                    'Applink Trx ID',
                    'Account',
                    'Debit',
                    'Credit',
                    'Balance'
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
                $data = ReportLink::updateOrCreate(
                    ['Trans_Initiate_Time' => $result[11]],
                    [
                        'Top_Org_Name'=> $result[0],
                        'Top_Short_Code'=> $result[1],
                        'Biz_Org_Name'=> $result[2],
                        'Short_Code'=> $result[3],
                        'Orderid'=> $result[4],
                        'Linkedorderid'=> $result[5],
                        'Linkedorder_Create_Time'=> $result[6],
                        'Linkedorder_End_Time'=> $result[7],
                        'Invoice_ID'=> $result[8],
                        'Trans_End_Time'=> $result[9],
                        'Trans_Initiate_Time'=> $result[10],
                        'Transaction_Type'=> $result[11],
                        'Transaction_Scenario'=> $result[12],
                        'Trans_Status'=> $result[13],
                        'Gateway'=> $result[14],
                        'Tid'=> $result[15],
                        'Trx_ID'=> $result[16],
                        'Trans_Ref_Number'=> $result[17],
                        'Bill_Ref_Number'=> $result[18],
                        'Note'=> $result[19],
                        'Recharged_Msisdn'=> $result[20],
                        'Partner_Trx_ID'=> $result[21],
                        'Applink_Trx_ID'=> $result[22],
                        'Account'=> $result[23],
                        'Debit'=> $result[24],
                        'Credit'=> $result[25],
                        'Balance'=> $result[26]
                    ]
                );
            }
        }

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
