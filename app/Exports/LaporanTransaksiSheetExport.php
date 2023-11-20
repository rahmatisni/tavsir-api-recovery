<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanTransaksiSheetExport implements FromView, WithTitle
{
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('exports.laporan-transaksi', $this->data);
    }

    /**
     * @return string
    */
    public function title(): string
    {
        return $this->data['nama_tenant'];
    }
}
