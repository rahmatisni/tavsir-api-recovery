<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPenjualanExport implements FromView
{
    public function __construct($data)
    {
        $this->data = $data;
    }
   
    public function view(): View
    {
        return view('exports.laporan-penjualan',$this->data);
    }
}
