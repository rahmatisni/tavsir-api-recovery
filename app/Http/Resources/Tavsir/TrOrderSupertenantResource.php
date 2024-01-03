<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrOrderSupertenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $product_member = auth()->user()->tenant?->product?->pluck('id') ?? [];
        $detil_tenant = $this->detil->whereIn('product_id', $product_member);

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'consume_type' => $this->consume_type,
            'consume_type_label' => $this->consumeTypeLabel(),
            'nomor_name' => $this->nomor_name,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? null,
            'business_id' => $this->business_id,
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
            "payment_name" => $this->payment_method && $this->payment_method->name != null ? $this->payment_method->name : '',
            "payment_id" => $this->payment_id,
            "voucher_id" => $this->voucher_id,
            'casheer_id' => $this->casheer_id,
            // 'sub_total' => $this->sub_total,
            'sub_total' => $detil_tenant->sum(['total_price']),
            'discount' => $this->discount,
            // 'total' => $this->total,
            'total' => $detil_tenant->sum(['total_price']),
            'addon_total' => $this->addon_total,
            'addon_price' => $this->addon_price,
            'saldo_qr' => $this->saldo_qr ?? 0,
            'pay_amount' => $this->pay_amount,
            'casheer_name' => $this->casheer->name ?? '',
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'canceled_by' => $this->canceled_by,
            'canceled_name' => $this->canceled_name,
            'reason_cancel' => $this->reason_cancel,
            'payment' => $this->payment,
            'code_verif' => $this->code_verif,
            'rating' => $this->rating,
            'detil' => TrOrderDetilMemberResource::collection($detil_tenant),
        ];
    }
}
