<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanTransaksiExport implements WithMultipleSheets
{
    public function __construct($data)
    {
        $this->data = collect($data);
        $this->record = collect($data['record'] ?? []);
    }

    public function sheets(): array
    {
        $data_array = [];
        $record = [];
        $group = $this->record->groupBy('tenant');
        foreach ($group as $key => $value) {
            $record['nama_tenant'] = $key;
            $record['tanggal_awal'] = $this->data['tanggal_awal'];
            $record['tanggal_akhir'] = $this->data['tanggal_akhir'];
            $record['total_product'] = $value->sum('total_product');
            $record['fee'] = $value->sum('fee');
            $record['service_fee'] = $value->sum('service_fee');
            $record['total_sub_total'] = $value->sum('total_sub_total');
            $record['total_addon'] = $value->sum('total_addon');
            $record['total_total'] = $value->sum('total');
            $record['record'] = $value;
            $data_array[$key] =  new LaporanTransaksiSheetExport($record);
        }
        return $data_array;
    }
}
