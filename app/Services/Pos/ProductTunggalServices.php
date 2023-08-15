<?php

namespace App\Services\Pos;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransStock;
use Illuminate\Support\Facades\DB;

class ProductTunggalServices
{
    public function list($search = null, $filter = [])
    {
        return Product::with('category','customize','tenant')
                ->byType(ProductType::TUNGGAL)
                ->byTenant()
                ->myWhereLike(['name','sku'], $search)
                ->myWheres($filter)
                ->orderByRaw('stock = 0')->orderByRaw('is_active = 0')->orderBy('name', 'asc')
                ->paginate();
    }

    public function create(array $paylod)
    {
        DB::beginTransaction();
        try {
            $data = new Product();
            $data->type = ProductType::TUNGGAL;
            $data->tenant_id = auth()->user()->tenant_id;
            $data->fill($paylod);
            $data->save();
            if($paylod['is_composit'] == 0){
                $data->trans_stock()->create([
                    'stock_type' => TransStock::INIT,
                    'recent_stock' => 0,
                    'stock_amount' => $data->stock,
                    'price_capital' => $data->price_capital,
                    'created_by' => auth()->user()->id,
                ]);
            }else{
                $data->trans_product_raw()->sync($paylod['raw']);
            }
            $data->customize()->sync($paylod['customize'] ?? []);
            Db::commit();
            return $data;
        } catch (\Throwable $th) {
            Db::rollBack();
            abort(422, $th->getMessage() ?? 'Throw Error');
        }
    }

    public function show($id)
    {
        return Product::byTenant()->byType(ProductType::TUNGGAL)->findOrFail($id);
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
        try {
            $data->fill($paylod);
            $data->save();
            if($paylod['is_composit'] == 1){
                $data->trans_product_raw()->sync($paylod['raw']);
            }else{
                $data->trans_product_raw()->sync([]);
            }
            $data->customize()->sync($paylod['customize'] ?? []);
            Db::commit();
            return $data;
        } catch (\Throwable $th) {
            Db::rollBack();
            abort(422, $th->getMessage() ?? 'Throw Error');
        }
    }

    public function delete($id)
    {
        $data = $this->show($id);
        $data->trans_stock()->delete();
        $data->customize()->sync([]);
        $data->trans_product_raw()->sync([]);
        $data->delete();
        return true;
    }
}
