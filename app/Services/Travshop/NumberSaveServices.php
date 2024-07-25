<?php

namespace App\Services\Travshop;

use App\Models\Category;
use App\Models\Product;
use App\Models\Constanta\ProductType;
use App\Models\NumberSave;

class NumberSaveServices
{
    public function list($filter)
    {
        // return NumberSave::where('customer_id', $filter)->get();
        return NumberSave::where('customer_id', $filter)
            ->orderBy('created_at', 'desc')
            ->groupBy('number')
            ->get();

    }

    public function create(array $paylod)
    {
        $data = new NumberSave();
        $data->fill($paylod);
        $data->save();
        return $data;
    }

    public function show($id)
    {
        return NumberSave::findOrFail($id);
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
