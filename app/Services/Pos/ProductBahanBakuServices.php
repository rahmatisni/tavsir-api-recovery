<?php

namespace App\Services\Pos;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\RawProduct;
use App\Models\TransStock;
use App\Models\TransStockRaw;
use Illuminate\Support\Facades\DB;

class ProductBahanBakuServices
{
    public function list($search = null)
    {
        return Product::byType(ProductType::BAHAN_BAKU)
                        ->byTenant()
                        ->myWhereLike(['name','sku'], $search)
                        ->orderByDesc('id')
                        ->paginate();
    }

    public function create(array $paylod)
    {
        DB::beginTransaction();
        try {
            $data = new Product();
            $data->type = ProductType::BAHAN_BAKU;
            $data->tenant_id = auth()->user()->tenant_id;
            $data->fill($paylod);
            $data->price_min = $data->price;
            $data->price_max = $data->price;
            $data->price_capital = $data->price_capital;
            $data->save();
            $data->trans_stock()->create([
                'stock_type' => TransStock::INIT,
                'recent_stock' => 0,
                'stock_amount' => $data->stock,
                'price_capital' => $data->price_capital,
                'created_by' => auth()->user()->id,
            ]);
            Db::commit();
            return $data;
        } catch (\Throwable $th) {
            Db::rollBack();
            abort(422, $th->getMessage() ?? 'Throw Error');
        }
    }

    public function show($id)
    {
        return Product::byType(ProductType::BAHAN_BAKU)->byTenant()->findOrFail($id);
    }

    public function changeStatus($id)
    {
        $data = $this->show($id);
        $data->is_active = $data->is_active == 1 ? 0 : 1;
        $data->save();
        return ['is_active' => $data->is_active ];
    }


    public function update($id, array $paylod)
    {
        $data = $this->show($id);
        $data->fill($paylod);
        $data->save();
        return $data;
    }

    public function delete($id)
    {
        $data = $this->show($id);
        $data->trans_stock()->delete();
        $data->delete();
        return true;
    }
}
