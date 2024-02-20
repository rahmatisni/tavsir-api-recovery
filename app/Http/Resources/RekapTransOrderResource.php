<?php

namespace App\Http\Resources;

use Brick\Math\BigDecimal;
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
            'created_at' => (string) $this->created_at,
            'trans_order_id' => $this->id,
            'order_id' => $this->order_id,
            'total_product' => $this->detil->count(),
            'total' => $this->status == 'DONE' ? $this->total : -$this->total,
            'sub_total' => $this->status == 'DONE' ? $this->sub_total : -$this->sub_total,
            'status' => $this->status,
            'payment_method' => $this->payment_method->name ?? '',
            'bank_name' => $this->trans_edc->bank->name ?? '',
            'order_type' => $this->order_type,
            'order_type_label' => $this->labelOrderType(),
            'sharing_code' => $this->status == 'DONE' ? (json_decode($this->sharing_code)?? [(string)$this->tenant_id]) : [],
            'sharing_proportion' => $this->status == 'DONE' ? (json_decode($this->sharing_proportion)??[100]) : [],
            'sharing_amount' => $this->status == 'DONE' ? (count($resultArray) > 1 ? $resultArray:[(string)$this->total]) : [],
            'invoice_id' => $this->invoice_id ?? null

        ];
    }
}
