<?php

namespace App\Exports;

use App\Models\Constanta\ProductType;
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
        return Product::byTenant()->byType(ProductType::PRODUCT)->get()->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'id' => $item->id,
                'product' => $item->name,
                'kategori' => $item->category?->name,
                'stock_awal' => $item->stock ?? 0,
                'stock_masuk' => 0,
                'keterangan' => $item->description
            ];
        });
    }

    public function headings(): array
    {
        return ["NO", "ID", "PRODUCT", "KATEGORI", "STOK AWAL", "STOK MASUK", "KETERANGAN"];
    }
}
