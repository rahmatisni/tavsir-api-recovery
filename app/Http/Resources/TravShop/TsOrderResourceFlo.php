<?php

namespace App\Http\Resources\TravShop;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use App\Services\External\JatelindoService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Summary of App\Http\Resources\TravShop\rupiah
 * @param mixed $angka
 * @return string
 */
function rupiah($angka)
{

    $hasil_rupiah = "Rp. " . number_format($angka, 0, ',', '.');
    return $hasil_rupiah;

}

function cleansings($angka)
{

    $variable = $str = ltrim($angka, "0");
    ;
    return $variable;

}

class TsOrderResourceFlo extends JsonResource
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
        $product_kios_bank = $this->productKiosbank();
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
            'payment_method' => $this->payment_method?->only('code_name', 'code', 'name', 'id', 'logo_url', 'payment_method_code'),
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
            'payment' => $this->payment->data ?? null,
            // 'payment' => $this->payment?->data['responseData'] ?? $this->payment->data ?? null,
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
            'self_order_url' => ENV('URL_SELF_ORDER').'/?trans_order_id='.$this->id
        ];
    }


}