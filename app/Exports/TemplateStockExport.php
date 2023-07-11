<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateStockExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::byTenant()->get()->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'id' => $item->id,
                'product' => $item->name,
                'stock' => $item->stock,
                'keterangan' => $item->description
            ];
        });
    }

    public function headings(): array
    {
        return ["NO", "ID", "PRODUCT", "STOCK", "KETERANGAN"];
    }
}
