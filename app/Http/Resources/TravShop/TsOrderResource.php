<?php

namespace App\Http\Resources\TravShop;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use App\Services\External\JatelindoService;
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
            $product_kios_bank = $this->productKiosbank();

            if ($product_kios_bank) {
                $product_kios = $product_kios_bank->only([
                    'kategori',
                    'sub_kategori',
                    'kode',
                    'harga',
                    'name',
                    'logo_url'
                ]);
                $product_kios['handphone'] = $product[1];
                $product_kios['Nomor_Cust'] = $product[1];
                if ($product_kios_bank->integrator == 'JATELINDO') {
                    unset($product_kios['handphone']);
                    unset($product_kios['kode']);
                   

                    
                    $product_kios = array_merge($product_kios, JatelindoService::infoPelanggan($this->log_kiosbank, $this->status));
                    // if ($this->status === TransOrder::WAITING_PAYMENT) {

                        // $daya = $product_kios['Daya'];
                        // $product_kios['Daya'] = str_pad($daya, 9, "0", STR_PAD_LEFT);
                        unset($product_kios['Ref_ID']);
                        // $product_kios['Nama_Produk'] = $product_kios['name'];
                        // unset($product_kios['name']);
                        unset($product_kios['logo_url']);
                        unset($product_kios['Transaksi_ID']);
                        unset($product_kios['Vending_Number']);
                        $note = $product_kios['Informasi'] ?? null;
                        $title = 'STRUK PEMBELIAN LISTRIK PRABAYAR';
                        unset($product_kios['Informasi']);
                        unset($product_kios['Flag']);
                        unset($product_kios['Pilihan_Pembelian']);
                        unset($product_kios['Transaksi_ID']);
                        unset($product_kios['Total_Token_Unsold']);
                        unset($product_kios['Pilihan_Token']);
                        unset($product_kios['Token_Unsold_1']);
                        unset($product_kios['Token_Unsold_2']);

                    // }
                    // if ($this->status === TransOrder::DONE) {
                    //     unset($product_kios['flag']);
                    //     unset($product_kios['transaksi_id']);
                    //     unset($product_kios['ref_id']);
                    //     unset($product_kios['total_token_unsold']);
                    //     unset($product_kios['pilihan_token']);
                    //     unset($product_kios['token_unsold_1']);
                    //     unset($product_kios['token_unsold_2']);
                    // }
                }
            }
            $temp = $this->log_kiosbank?->data['data'] ?? null;


            if ($temp) {
                $temps = $this->log_kiosbank->data;
                $temps['data'] = [];
                // $slice = ['harga_kios'];
                $param = ['Admin_Bank', 'Total', 'Jumlah_Pembayaran', 'Angsuran', 'Materai', 'Pembelian_Token', 'PPJ', 'PPN', 'Rp_Stroom/token', 'Total_Pembayaran'];
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
                        case ($key == "diskon"):
                            $key = 'Diskon';
                            break;
                        default:
                            $key = $key;
                    }

                    if (in_array($key, $param)) {
                        $temps['data'][$key] = rupiah((int) $val);
                    } elseif (in_array($key, $minus)) {
                    } elseif (in_array($key, $slice)) {
                    } elseif (in_array($key, $cleansing)) {
                        $temps['data'][$key] = cleansings($val);
                    } else {

                        $temps['data'][$key] = $val;
                    }
                }
            }


            // $temps['data']['Diskon'] = '-' . rupiah((int) $this->discount);
            $temps['data']['Diskon'] = $this->discount == 0 ? rupiah((int) $this->discount) : '-'.rupiah((int) $this->discount);
            unset($temps['sessionID']);
            // unset($temps['customerID']);
            unset($temps['merchantID']);
            unset($temps['referenceID']);
            unset($temps['productID']);
            unset($temps['diskon']);

            $rest_area_name = 'Travoy';
            $tenant_name = 'Multibiller';
        }

        $log_kios_bank = $this->order_type === 'ORDER_TRAVOY' ? ($product_kios_bank?->integrator == 'JATELINDO' ? ['data' => $product_kios] : ($temps ?? $this->log_kiosbank)) : null;
        unset($log_kios_bank['data']['name']);
        $logo = $this->tenant->logo ?? null;

        return [
            "id" => $this->id,
            'rest_area_name' => $rest_area_name,
            'business_name' => $this->tenant->business->name ?? null,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $tenant_name,
            'tenant_photo' => $this->tenant ? ($this->tenant->photo_url ? asset($this->tenant->photo_url) : null) : null,
            'tenant_is_open' => $this->tenant ? ($this->tenant->is_open == 1 ? true : false) : false,
            'order_id' => $this->order_id,
            'order_type' => $this->order_type,
            'consume_type' => $this->consume_type,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'nomor_name' => $this->nomor_name ?? '',
            'payment_method' => $this->payment_method?->only('code_name', 'code', 'name', 'id', 'logo_url'),
            'sub_total' => $this->sub_total,
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'discount' => $this->discount,
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
            'paid_date' => $this->payment?->updated_at->format('Y-m-d H:i:s') ?? null,
            // 'payment' => $this->payment->data ?? null,
            'payment' => $this->payment?->data['responseData'] ?? $this->payment->data ?? null,
            'log_kiosbank' => $log_kios_bank ?? null,
            'addon_total' => $this->addon_total,
            'addon_price' => $this->addon_price,
            'detil_kios' => $product_kios,
            "logo" => $logo ? asset($logo) : null,
            "additional_information" => $this->tenant->additional_information ?? null,
            "instagram" => $this->tenant->instagram ?? null,
            "facebook" => $this->tenant->facebook ?? null,
            "website" => $this->tenant->website ?? null,
            "note" => $this->tenant->note ?? $note ?? null,
            'title' => $title ?? null,
            'detil' => TsOrderDetilResource::collection($this->detil),
        ];
    }


}