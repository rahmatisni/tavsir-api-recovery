<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanRekonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pairStrings = explode(',', substr($this->sharing_amount, 1, -1));

        $resultArray = [];


        foreach ($pairStrings as $pairString) {
            $dotPosition = strpos($pairString, '.');

            // Define the number of characters to keep after the dot
            $charactersToKeep = 2;

            // Check if the dot was found and if there are enough characters after it
            if ($dotPosition !== false && $dotPosition + $charactersToKeep < strlen($pairString)) {
                // Trim characters after the dot + 2
                $trimmedString = substr($pairString, 0, $dotPosition + $charactersToKeep + 1);
            } else {
                // No trimming needed
                $trimmedString = $pairString;
            }


            $resultArray[] = $trimmedString;
        }
        
        return [
            "trans_order_id"=> $this->trans_order_id,
            "order_id"=> $this->order_id,
            "business_id"=> $this->business_id,
            "tenant_id"=> $this->tenant_id,
            "order_type"=> $this->order_type,
            "sub_total"=> $this->sub_total,
            "fee"=> $this->fee,
            "service_fee"=> $this->service_fee,
            "total"=> $this->total,
            "metode_bayar"=> $this->metode_bayar,
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
            "customer_name"=> $this->customer_name,
            "customer_phone"=> $this->customer_phone,
            "payment_method_id"=> $this->payment_method_id,
            "status"=>$this->status,
            'sharing_code' => $this->status == 'DONE' || $this->status == 'PAYMENT_SUCCESS' ? (json_decode($this->sharing_code) ?? [(string) $this->tenant_id]) : [],
            'sharing_proportion' => $this->status == 'DONE' || $this->status == 'PAYMENT_SUCCESS' ? (json_decode($this->sharing_proportion) ?? [100]) : [],
            'sharing_amount' => $this->status == 'DONE' || $this->status == 'PAYMENT_SUCCESS' ? (count($resultArray) > 1 ?  $resultArray:[$this->total - $this->service_fee]) : [],
            "refnum"=> $this->refnum,
            "paid_date"=> $this->paid_date,
            "valid"=> (int)$this->valid,
            "derek" => $this->detilDerek,
            "status_report" => $this->detilReport->Status_Report ?? '-'
        ];
    }
}
