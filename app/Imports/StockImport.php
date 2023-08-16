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
            if ($key != 0) {
                try {
                    $product = Product::byTenant()->byType(ProductType::PRODUCT)->find($row[1]);
                    if (!$product) {
                        throw new Exception('Product ID ' . $row[1] . ' invalid');
                    }
                    if (!is_numeric($row[3])) {
                        throw new Exception('Stock ' . $row[3] . ' invalid numeric');
                    } else {
                        if ($row[3] < 0) {
                            throw new Exception('Stock ' . $row[3] . ' value cannot minus');
                        }
                    }

                    $data = new TransStock();
                    $data->product_id = $row[1];
                    $data->stock_type = $this->type;
                    $data->recent_stock = $data->product->stock;
                    $data->stock_amount = $row[3];
                    $data->keterangan = $row[4];
                    $data->save();
                    $data->product()->update(['stock' => $data->lates_stock]);
                    array_push($valid, $row);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    array_push($invalid, $th->getMessage());
                }
            }
        }

        $invalid_data = count($invalid);
        $valid_data = count($valid);
        $status = $invalid_data > 0 ? false : true;

        if ($status) {
            DB::rollBack();
        } else {
            DB::commit();
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
