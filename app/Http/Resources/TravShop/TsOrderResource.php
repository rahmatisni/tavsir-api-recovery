<?php

namespace App\Http\Resources\TravShop;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use Illuminate\Http\Resources\Json\JsonResource;

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
        
        if($this->order_type == TransOrder::ORDER_TRAVOY)
        {
            $product = explode('-',$this->order_id);
            $product_kios = ProductKiosBank::where('kode',$product[0])
            ->select([
                'kategori',
                'sub_kategori',
                'kode',
                'name'
            ])
            ->first();
            if($product_kios)
            {
                $product_kios = $product_kios->toArray();
                $product_kios['handphone'] = $product[1];
            }
            $temp = $this->log_kiosbank?->data['data'];

            if ($temp)
            {
                $temps = $this->log_kiosbank->data;
                $temps['data'] = [];
        
                foreach($temp as $key => $val) {
                    $key = ucwords(preg_replace("/(?<=[a-zA-Z])(?=[A-Z])/", "_", $key));
                    if ($key == ('Tagihan' || 'tagihan')){
                        $temps['data'][$key] = 'asd';
                    }
                    else {
                        $temps['data'][$key] = $val;
                    }
                }
            }
        }

       
      
       

        return [
            "id" => $this->id,
            'rest_area_name' => $this->rest_area?->name ?? null,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? null,
            'tenant_photo' => $this->tenant ? ($this->tenant->photo_url ? asset($this->tenant->photo_url) : null) : null,
            'tenant_is_open' => $this->tenant ? ($this->tenant->is_open == 1 ? true : false) : false,
            'order_id' => $this->order_id,
            'order_type' => $this->order_type,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'payment_method' => $this->payment_method?->only('code_name','code','name','id'), 
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
            // 'log_kiosbank' => $this->log_kiosbank,
            'log_kiosbank' => $temps ?? $this->log_kiosbank,
            'detil' => TsOrderDetilResource::collection($this->detil),
            'detil_kios' => $product_kios
        ];
    }
}
