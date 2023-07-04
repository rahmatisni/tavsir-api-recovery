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

function cleansings($angka)
{

    $variable = $str = ltrim($angka, "0"); 
    ;
    return $variable;

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
                $param = ['Admin_Bank', 'Total', 'Jumlah_Pembayaran','Angsuran','Materai', 'Pembelian_Token','PPJ','PPN','Rp_Stroom/token','Total_Pembayaran'];
                $slice = ['Harga_kios'];
                $cleansing = ['Daya', 'Jumlah_KWH'];
                $minus = ['Diskon'];

                // dd($temps);
                foreach ($temp as $key => $val) {
                   
                    $key = ucwords(preg_replace("/(?<=[a-zA-Z])(?=[A-Z])/", "_", $key));
                    switch ($key) {
                        case ($key == "A_B"):
                            $key = 'Admin_Bank';
                            break;
                        case ($key == "D_Y"):
                            $key = 'Daya';
                            break;
                        case ($key == "I_D"):
                            $key = 'ID_Pelanggan';
                            break;
                        case ($key == "M_N"):
                            $key = 'Nomor_Meter';
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
                            $key = 'Info_Text';
                            break;
                        case ($key == "K_H"):
                            $key = 'Jumlah_KWH';
                            break;
                        case ($key == "M_T"):
                            $key = 'Materai';
                            break;
                        case ($key == "P_B"):
                            $key = 'Pembelian_Token';
                            break;
                        case ($key == "P_J"):
                            $key = 'PPJ';
                            break;
                        case ($key == "P_N"):
                            $key = 'PPN';
                            break;
                        case ($key == "R_F"):
                            $key = 'No_Ref';
                            break;
                        case ($key == "R_S"):
                            $key = 'Rp_Stroom/token';
                            break;
                        case ($key == "T_K"):
                            $key = 'Token';
                            break;
                        case ($key == "T_T"):
                            $key = 'Total_Pembayaran';
                            break;
                        // case ($key == "diskon"):
                        //     $key = 'Diskon';
                        //     break;
                        default:
                            $key = $key;
                    }
                 
                    if (in_array($key, $param)) {
                        // $temps['data'][$key] = 1;
                        $temps['data'][$key] = rupiah((int) $val);
                    } 
                    // elseif (in_array($key, $minus)) {
                    //     $temps['data'][$key] = '-'.rupiah((int) $val);
                    // } 
                    elseif (in_array($key, $slice)) {
                    } 
                 elseif (in_array($key, $cleansing)) {
                    $temps['data'][$key] = cleansings($val);
                }
                    else {
                        // if ( $temps['data'][$key] == 'Harga_kios'){
                        //     $temps['data'][$key] = 2;
                        // }
                        $temps['data'][$key] = $val;

                    }
                }
            }
            $temps['data']['Discount'] = '-'.rupiah((int) $this->discount);


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
            'nomor_name' => $this->nomor_name ?? '',
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
            'addon_total' => $this->addon_total,
            'addon_price' => $this->addon_price,
            'detil_kios' => $product_kios,
            'detil' => TsOrderDetilResource::collection($this->detil),
        ];
    }


}