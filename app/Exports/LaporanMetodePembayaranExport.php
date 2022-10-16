<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanMetodePembayaranExport implements FromView
{
    public function __construct($data)
    {
        $this->data = $data;
    }
   
    public function view(): View
    {
        return view('exports.laporan-metode-pembayaran', [
            'record' => $this->data
        ]);
    }
}
