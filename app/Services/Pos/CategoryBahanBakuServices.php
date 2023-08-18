<?php

namespace App\Services\Pos;

use App\Models\Category;
use App\Models\Constanta\ProductType;

class CategoryBahanBakuServices
{
    public function list($search = null)
    {
        return Category::byType(ProductType::BAHAN_BAKU)
                        ->byTenant()
                        ->myWhereLike(['name'], $search)
                        ->orderBy('name')
                        ->paginate();
    }

    public function create(array $paylod)
    {
        $data = new Category();
        $data->type = ProductType::BAHAN_BAKU;
        $data->tenant_id = auth()->user()->tenant_id;
        $data->fill($paylod);
        $data->save();
        return $data;
    }

    public function show($id)
    {
        return Category::byType(ProductType::BAHAN_BAKU)->byTenant()->findOrFail($id);
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
        $data->delete();
        return true;
    }
}
