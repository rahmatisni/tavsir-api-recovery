<?php

namespace App\Exports;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TemplateStockExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    public function __construct(protected string $type)
    {
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::byTenant()->nonComposit()->whereIn('type', [ProductType::PRODUCT, ProductType::BAHAN_BAKU])->get()->map(function ($item, $key) {
            $data =  [
                'no' => $key + 1,
                'id' => $item->id,
                'product' => $item->name,
                'kategori' => $item->category?->name,
                'stok_awal' => $item->stock ?? 0,
                'stok_awal' => $item->stock ?? 0,
            ];

            if($this->type == 'in'){
                $data['stok_masuk'] = 0;
            }

            if($this->type == 'out'){
                $data['stok_keluar'] = 0;
            }

            $data['keterangan'] = $item->description;

            return $data;
        });
    }

    public function headings(): array
    {
        $type = "STOK MASUK";
        if($this->type == 'out'){
            $type = "STOK KELUAR";
        }
        return ["NO", "ID", "PRODUCT", "KATEGORI", "STOK AWAL", $type, "KETERANGAN"];
    }
}
