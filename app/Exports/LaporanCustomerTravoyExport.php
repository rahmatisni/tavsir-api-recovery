<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanCustomerTravoyExport implements FromView, WithColumnFormatting
{
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function view(): View
    {
        return view('exports.laporan-customer-travoy', $this->data);
    }
}
