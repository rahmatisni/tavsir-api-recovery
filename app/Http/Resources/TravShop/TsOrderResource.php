<?php

namespace App\Http\Resources\TravShop;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Summary of App\Http\Resources\TravShop\rupiah
 * @param mixed $angka
 * @return string
 */
function rupiah($angka)
{

    $hasil_rupiah = "Rp." . number_format($angka, 0, ',', '.');
    return $hasil_rupiah;

}

class TsOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function toArray($request)
    {

        $product_kios = null;
        $rest_area_name = $this->rest_area?->name ?? null;
        $tenant_name = $this->tenant->name ?? null;
        if ($this->order_type == TransOrder::ORDER_TRAVOY) {
            $product = explode('-', $this->order_id);

            $product_kios = $this->getProductKios->where('kode', $product[0])->first();

            if ($product_kios) {
                $product_kios = $product_kios->toArray();
                $product_kios['handphone'] = $product[1];
            }
            $temp = $this->log_kiosbank?->data['data'] ?? null;


            if ($temp) {
                $temps = $this->log_kiosbank->data;
                $temps['data'] = [];
                // $slice = ['harga_kios'];
                $param = ['Admin_Bank', 'Total', 'Jumlah_Pembayaran'];
                $slice = ['Harga_kios'];
                // dd($temps);
                foreach ($temp as $key => $val) {
                    $key = ucwords(preg_replace("/(?<=[a-zA-Z])(?=[A-Z])/", "_", $key));
                    switch ($key) {
                        case ($key == "A_B"):
                            $key = 'AdminBank';
                            break;
                        case ($key == "D_Y"):
                            $key = 'Daya';
                            break;
                        case ($key == "I_D"):
                            $key = 'IDPelanggan';
                            break;
                        case ($key == "M_N"):
                            $key = 'NomorMeter';
                            break;
                        case ($key == "N_M"):
                            $key = 'Nama';
                            break;
                        case ($key == "T_F"):
                            $key = 'Tarif';
                            break;
                        case ($key == "A_S"):
                            $key = 'Angsuran';
                            break;
                        case ($key == "I_F"):
                            $key = 'InfoText';
                            break;
                        case ($key == "K_H"):
                            $key = 'JumlahKwh';
                            break;
                        case ($key == "M_T"):
                            $key = 'Materai';
                            break;
                        case ($key == "P_B"):
                            $key = 'PembelianToken';
                            break;
                        case ($key == "P_J"):
                            $key = 'PPJ';
                            break;
                        case ($key == "P_N"):
                            $key = 'PPN';
                            break;
                        case ($key == "R_F"):
                            $key = 'NoRef';
                            break;
                        case ($key == "R_S"):
                            $key = 'RpStroom/token';
                            break;
                        case ($key == "T_K"):
                            $key = 'Token';
                            break;
                        case ($key == "T_T"):
                            $key = 'TotalPembayaran	';
                            break;
                        default:
                            $key = $key;
                    }
                    if (in_array($key, $param)) {
                        // $temps['data'][$key] = 1;
                        $temps['data'][$key] = rupiah((int) $val);

                    } elseif (in_array($key, $slice)) {
                        // $temps['data'][$key] =1;

                    } else {
                        // if ( $temps['data'][$key] == 'Harga_kios'){
                        //     $temps['data'][$key] = 2;
                        // }
                        $temps['data'][$key] = $val;

                    }
                }
            }

            $rest_area_name = 'Travoy';
            $tenant_name = 'Multibiller';
        }


        return [
            "id" => $this->id,
            'rest_area_name' => $rest_area_name,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $tenant_name,
            'tenant_photo' => $this->tenant ? ($this->tenant->photo_url ? asset($this->tenant->photo_url) : null) : null,
            'tenant_is_open' => $this->tenant ? ($this->tenant->is_open == 1 ? true : false) : false,
            'order_id' => $this->order_id,
            'order_type' => $this->order_type,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'payment_method' => $this->payment_method?->only('code_name', 'code', 'name', 'id'),
            'sub_total' => $this->sub_total,
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'total' => $this->total,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'canceled_by' => $this->canceled_by,
            'canceled_name' => $this->canceled_name,
            'reason_cancel' => $this->reason_cancel,
            'casheer_name' => $this->casheer->name ?? '',
            'total_pesanan' => $this->detil->count(),
            'rating' => $this->rating,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'payment' => $this->payment->data ?? null,
            'log_kiosbank' => $temps ?? $this->log_kiosbank,
            'detil' => TsOrderDetilResource::collection($this->detil),
            'detil_kios' => $product_kios
        ];
    }


}