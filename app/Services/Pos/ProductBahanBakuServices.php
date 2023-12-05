<?php

namespace App\Services\Pos;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\RawProduct;
use App\Models\TransProductRaw;
use App\Models\TransStock;
use App\Models\TransStockRaw;
use Illuminate\Support\Facades\DB;

class ProductBahanBakuServices
{
    public function list($search = null, $filter = [])
    {
        return Product::byType(ProductType::BAHAN_BAKU)
                        ->byTenant()
                        ->myWhereLikeStart(['name','sku','is_active','category_id'], $search)
                        ->myWheres($filter)
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
            $data->price_min = $data->price_capital;
            $data->price_max = $data->price_capital;
            $data->price_capital = $data->price_capital;
            $data->save();
            $data->trans_stock()->create([
                'stock_type' => TransStock::INIT,
                'recent_stock' => 0,
                'stock_amount' => $data->stock,
                'price_capital' => $data->price_capital,
                'total_capital' => $data->price_capital * $data->stock,
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
        $cek = TransProductRaw::where('child_id', $id)->count();
        if($cek > 0){
            helperThrowErrorvalidation([
                'id' => 'Bahan baku dipakai di '.$cek.' product',
            ]);
        }
        try {
            DB::beginTransaction();
            $data->trans_stock()->create([
                'stock_type' => TransStock::OUT,
                'recent_stock' => $data->stock,
                'stock_amount' => $data->stock,
                'price_capital' => $data->price_capital,
                'total_capital' => $data->price_capital * $data->stock,
                'created_by' => auth()->user()->id,
            ]);
            $data->delete();
            Db::commit();
            return true;

        } catch (\Throwable $th) {
            Db::rollBack();
            throw $th;
        }
    }
}
