<?php

namespace App\Models;

use App\Models\BaseModel;

class ReportLink extends BaseModel
{
    protected $table = 'report_linkaja';

    protected $fillable = [
        'id',
        'Top_Org_Name',
        'Top_Short_Code',
        'Biz_Org_Name',
        'Short_Code',
        'Orderid',
        'Linkedorderid',
        'Linkedorder_Create_Time',
        'Linkedorder_End_Time',
        'Invoice_ID',
        'Trans_End_Time',
        'Trans_Initiate_Time',
        'Transaction_Type',
        'Transaction_Scenario',
        'Trans_Status',
        'Gateway',
        'Tid',
        'Trx_ID',
        'Trans_Ref_Number',
        'Bill_Ref_Number',
        'Note',
        'Recharged_Msisdn',
        'Partner_Trx_ID',
        'Applink_Trx_ID',
        'Account',
        'Debit',
        'Credit',
        'Balance',
    ];

    // public function voucher_detail()
    // {
    //     return $this->belongsTo(Voucher::class, 'id');
    // }
}
