<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrOrderResourceDerek extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $logo = $this->tenant->logo ?? null;
        // return [
        //     'id' => $this->id,
        //     'order_id' => $this->order_id,
        //     'consume_type' => $this->consume_type,
        //     'nomor_name' => $this->nomor_name,
        //     'tenant_id' => $this->tenant_id,
        //     'tenant_name' => $this->tenant->name ?? null,
        //     'business_id' => $this->business_id,
        //     'business_name' => $this->tenant->business->name ?? null,
        //     'merchant_id' => $this->merchant_id,
        //     'rest_area_id' => $this->tenant->rest_area_id ?? null,
        //     'rest_area_name' => $this->tenant->rest_area->name ?? null,
        //     'sub_merchant_id' => $this->sub_merchant_id,
        //     "payment_method_id" => $this->payment_method_id,
        //     "order_type" => @$this->order_type,
        //     "customer_id" => @$this->customer_id,
        //     "customer_name" => @$this->customer_name,
        //     "customer_phone" => @$this->customer_phone,
        //     "created_at" => $this->created_at->format('Y-m-d H:i:s'),
        //     "refund_at" => $this->status == 'REFUND' ? $this->updated_at->format('Y-m-d H:i:s'): null,
        //     "payment_name" => $this->payment_method && $this->payment_method->name != null ? $this->payment_method->name : '',
        //     "payment_id" => $this->payment_id,
        //     "voucher_id" => $this->voucher_id,
        //     'casheer_id' => $this->casheer_id ?? null,
        //     'sub_total' => $this->sub_total,
        //     'discount' => $this->discount,
        //     'total' => $this->total,
        //     'saldo_qr' => $this->saldo_qr ?? 0,
        //     'pay_amount' => $this->pay_amount,
        //     'casheer_name' => $this->casheer->name ?? '',
        //     'fee' => $this->fee,
        //     'service_fee' => $this->service_fee,
        //     'status' => $this->status,
        //     'canceled_by' => $this->canceled_by,
        //     'canceled_name' => $this->canceled_name,
        //     'bank_name' => $this->trans_edc->bank->name ?? '',
        //     'payment' => $this->payment,
        //     'code_verif' => $this->code_verif,
        //     'rating' => $this->rating,
        //     'addon_total' => $this->addon_total,
        //     'addon_price' => $this->addon_price,
        //     "logo" => $logo ? asset($logo) : null,
        //     "additional_information" => $this->tenant->additional_information ?? null,
        //     "instagram" => $this->tenant->instagram ?? null,
        //     "facebook" => $this->tenant->facebook ?? null,
        //     "website" => $this->tenant->website ?? null,
        //     "note" => $this->tenant->note ?? null,
        //     "claim_state" => $this->is_solve_derek === 3 ? true : false
        // ];
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
            'id' => $this->id,
            'order_id' => $this->order_id,
            'consume_type' => $this->consume_type,
            'consume_type_label' => 'DEREK_ONLINE',
            'nomor_name' => $this->nomor_name,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? null,
            'business_id' => $this->business_id,
            'business_name' => $this->tenant->business->name ?? null,
            'merchant_id' => $this->merchant_id,
            'rest_area_id' => $this->tenant->rest_area_id ?? null,
            'rest_area_name' => $this->tenant->rest_area->name ?? null,
            'sub_merchant_id' => $this->sub_merchant_id,
            "payment_method_id" => $this->payment_method_id,
            "order_type" => @$this->order_type,
            "customer_id" => @$this->customer_id,
            "customer_name" => @$this->customer_name,
            "customer_phone" => @$this->customer_phone,
            "created_at" => $this->created_at->format('Y-m-d H:i:s'),
            "refund_at" => $this->status == 'REFUND' ? $this->updated_at->format('Y-m-d H:i:s'): null,
            "payment_name" => $this->payment_method && $this->payment_method->name != null ? $this->payment_method->name : '',
            "payment_id" => $this->payment_id,
            "voucher_id" => $this->voucher_id,
            'casheer_id' => $this->casheer_id ?? null,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'total' => $this->total,
            'saldo_qr' => $this->saldo_qr ?? 0,
            'pay_amount' => $this->pay_amount,
            'casheer_name' => $this->casheer->name ?? '',
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'status' => $this->status,
            // 'status_label' => $this->statusLabel(),
            'canceled_by' => $this->canceled_by,
            'canceled_name' => $this->canceled_name,
            'bank_name' => $this->trans_edc->bank->name ?? '',
            'payment' => $this->payment,
            'code_verif' => $this->code_verif,
            'rating' => $this->rating,
            'addon_total' => $this->addon_total,
            'addon_price' => $this->addon_price,
            "logo" => $logo ? asset($logo) : null,
            "additional_information" => $this->tenant->additional_information ?? null,
            "instagram" => $this->tenant->instagram ?? null,
            "facebook" => $this->tenant->facebook ?? null,
            "website" => $this->tenant->website ?? null,
            "note" => $this->tenant->note ?? null,
        // 'detil' => TrOrderDetilResource::collection($this->detil),  
            ];
    }
}
