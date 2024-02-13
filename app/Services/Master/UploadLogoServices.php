<?php

namespace App\Services\Master;

use App\Models\KiosBank\ProductKiosBank;
use FontLib\Table\Type\name;
use Illuminate\Support\Facades\Storage;

class UploadLogoServices
{
    public function listKategori()
    {
        return ProductKiosBank::select('kategori')->distinct()->get();
    }

    public function upload($request)
    {
        $extenation = $request->logo->extension();
        $name = str_replace(' ','_',$request->kategori);
        $name = strtolower($name);
        $path = "logo/{$name}.{$extenation}";
        $result = Storage::put($path, file_get_contents($request->file('logo')));
        return [
            'status' => $result,
            'path' => asset($path)
        ];
    }

    public static function kategoriArray()
    {
        return ProductKiosBank::select('kategori')->distinct()->get()->pluck('kategori')->toArray();
    }
}
