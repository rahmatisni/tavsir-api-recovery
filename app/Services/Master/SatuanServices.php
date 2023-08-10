<?php

namespace App\Services\Master;

use App\Models\Satuan;

class SatuanServices
{
    public function list($tipe)
    {
        return Satuan::when($tipe, function($q) use($tipe){
            $q->where('type', $tipe);
        })->select('id','type','name')->get();
    }

    public function listTipe()
    {
        $data = [];
        foreach (Satuan::tipe() as $key => $value) {
            $data[$key]['name'] = $value;
        }
        return $data;
    }
}
