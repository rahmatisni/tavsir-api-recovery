<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RekapTransOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'created_at' => (string) $this->created_at,
            'trans_order_id' => $this->id,
            'order_id' => $this->order_id,
            'total_product' => $this->detil->count(),
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'payment_method' => $this->payment_method->name ?? '',
            'bank_name' => $this->trans_edc->bank->name ?? '',
            'order_type' => $this->order_type,
            'order_type_label' => $this->labelOrderType(),
            // 'sharing' => $this->asd(json_decode($this->sharing_code),json_decode($this->sharing_amount)),
            'sharing_code' => json_decode($this->sharing_code),
            'sharing_proportion' => json_decode($this->sharing_proportion),
            'sharing_amount' => json_decode($this->sharing_amount),
        ];
    }

    // public function asd($a,$b){
    //     $arr = [];
    //     foreach ($a as $key => $value) {
    //         // $arr[]=[$value=>$b[$key]];

    //     }
    //     return $arr;
    // }
}
