<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sharing;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\TransSharing;
use App\Models\User;

class TransSharingServices
{
    public function calculateSharing(TransOrder $order)
    {
        $sharing_rule = null;
        $tenant = $order->tenant;
        $supertenant = $order->supertenant;

        if($order->tenant_id){
            $sharing_rule =  Sharing::statusActive()->where('tenant_id', $tenant->id)->latest()->first();
        }

        if($order->supertenant_id){
            $sharing_rule = Sharing::statusActive()->where('supertenant_id', $supertenant->id)->latest()->first();
        }

        if ($sharing_rule) {
            $trans_shairng = TransSharing::create([
                'trans_order_id' => $order->id,
                'order_id' => $order->order_id,
                'order_type' => $order->labelOrderType(),
                'payment_method_id' => $order->payment_method_id,
                'payment_method_name' => $order->payment_method?->name,
                'sub_total' => $order->sub_total,

                'pengelola_id' => $sharing_rule->pengelola_id,
                'persentase_pengelola' => $sharing_rule->persentase_pengelola,
                'total_pengelola' => ($sharing_rule->persentase_pengelola / 100) * $order->sub_total,

                'supertenant_id' => $supertenant?->id,
                'persentase_supertenant' => $sharing_rule->persentase_supertenant,
                'total_supertenant' => ($sharing_rule->persentase_supertenant / 100) * $order->sub_total,

                'tenant_id' => $tenant?->id,
                'persentase_tenant' => $sharing_rule->persentase_tenant,
                'total_tenant' => ($sharing_rule->persentase_tenant / 100) * $order->sub_total,
            ]);
        }
    }
}
