<?php

namespace App\Imports;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransStock;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class StockImport implements ToCollection
{
    private $hasil;
    private $type;

    function __construct($type)
    {
        $this->type = $type;
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $valid = [];
        $valid_data = 0;
        $invalid = [];
        $invalid_data = 0;
        DB::beginTransaction();

            foreach ($collection as $key => $row) {
                if ($key == 0) {
                    $key_header = [
                        'NO',
                        'ID',
                        'PRODUCT',
                        'KATEGORI',
                        'STOK AWAL',
                        'STOK MASUK',
                        'KETERANGAN',
                    ];
                    if($row->toArray() != $key_header){
                        throw new Exception('Format Excel tidak sesuai');
                    }
                } else {
                    try {
                        $product = Product::byTenant()->nonComposit()->whereIn('type', [ProductType::PRODUCT, ProductType::BAHAN_BAKU])->find($row[1]);
                        if (!$product) {
                            throw new Exception('Product ID ' . $row[1] . ' invalid');
                        }
                        if (!is_numeric($row[5])) {
                            throw new Exception('Stok masuk row '.$key.' '. $row[5] . ' invalid numeric');
                        } else {
                            if ($row[5] < 0) {
                                throw new Exception('Stok awal row '.$key.' '. $row[5] . ' value cannot minus');
                            }
                        }
                        if($row[5] > 0){
                            if($this->type == TransStock::INIT){
                                $product->stock = max($row[5], 0);
                            }
    
                            if($this->type == TransStock::IN){
                                $product->stock = max($product->stock + $row[5], 0);
                            }
    
                            if($this->type == TransStock::OUT){
                                $product->stock = max($product->stock - $row[5], 0);
                            }
    
                            $product->save();
    
                            $data = new TransStock();
                            $data->product_id = $row[1];
                            $data->stock_type = $this->type;
                            $data->recent_stock = $product->stock;
                            $data->stock_amount = $row[5];
                            $data->keterangan = $row[6];
                            $data->save();
                            array_push($valid, $product->toArray());
                        }
                    } catch (\Throwable $th) {
                        array_push($invalid, $th->getMessage());
                    }
                }
            }

            $invalid_data = count($invalid);
            $valid_data = count($valid);
            $status = $invalid_data > 0 ? false : true;
    
            if ($status) {
                DB::commit();
            } else {
                DB::rollBack();
            }
    
            $hasil = [
                'status' => $status,
                'invalid_data' => $invalid_data,
                'valid_data' => $valid_data,
                'invalid' => $invalid,
            ];
            $this->hasil = $hasil;
    }

    public function getHasil()
    {
        return $this->hasil;
    }
}
