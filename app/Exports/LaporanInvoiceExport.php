<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanInvoiceExport implements FromView
{
    public function __construct($data)
    {
        $this->data = $data;
    }
   
    public function view(): View
    {
        return view('exports.laporan-invoice', [
            'record' => $this->data
        ]);
    }
}
