<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\RekapPendapatanResource;
use App\Http\Resources\RekapTransOrderResource;
use App\Models\TransOperational;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RekapPendapatanController extends Controller
{
    public function index()
    {
        $periode_berjalan = TransOperational::where('casheer_id', auth()->user()->id)
            ->whereNull('end_date')
            ->first();

        if (!$periode_berjalan) {
            return response()->json([
                'message' => 'Tidak ada periode berjalan'
            ], 404);
        }
        $data = TransOrder::with('payment_method')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('casheer_id', auth()->user()->id)
            ->whereBetween('created_at', [$periode_berjalan->start_date, Carbon::now()])
            ->when($payment_method_id = request('payment_method_id'), function ($q) use ($payment_method_id) {
                $q->where('payment_method_id', $payment_method_id);
            })
            ->when($order_type = request('order_type'), function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })
            ->when($order_id = request('order_id'), function ($q) use ($order_id) {
                $q->where('order_id', 'like', '%' . $order_id . '%');
            })
            ->when($sort = request('sort'), function ($q) use ($sort) {
                if (request('sort')) {
                    $sort = explode('&', request('sort'));
                    $q->orderBy($sort[0], $sort[1]);
                } else {
                    $q->orderBy('created_at', 'desc');
                }
            })->get();

        $data_all = $data->where('status','DONE');

        $cash = $data_all;
        $qr = $data_all;
        $digital = $data_all;
        $mandiri_va = $data_all;
        $edc = $data_all;
        $mandiri_dd = $data_all;
        $bri_va = $data_all;
        $bri_dd = $data_all;
        $link_aja = $data_all;
        $bni_va = $data_all;
        $digital = $data_all;
        $total_pendapatan = $data_all;
        $total_addon = $data_all;

        $cash = $cash->where('payment_method.code_name', 'cash')->sum('sub_total') + $cash->where('payment_method.code_name', 'cash')->sum('addon_total');
        $qr = $qr->where('payment_method.code_name', 'tav_qr')->sum('sub_total') + $qr->where('payment_method.code_name', 'tav_qr')->sum('addon_total');

        $mandiri_va = $mandiri_va->where('payment_method.code_name', 'pg_va_mandiri')->sum('sub_total') + $mandiri_va->where('payment_method.code_name', 'pg_va_mandiri')->sum('addon_total');
        $mandiri_dd = $mandiri_dd->where('payment_method.code_name', 'pg_dd_mandiri')->sum('sub_total') + $mandiri_dd->where('payment_method.code_name', 'pg_dd_mandiri')->sum('addon_total');
        $bri_va = $bri_va->where('payment_method.code_name', 'pg_va_bri')->sum('sub_total') + $bri_va->where('payment_method.code_name', 'pg_va_bri')->sum('addon_total');
        $bri_dd = $bri_dd->where('payment_method.code_name', 'pg_dd_bri')->sum('sub_total') + $bri_dd->where('payment_method.code_name', 'pg_dd_bri')->sum('addon_total');
        $link_aja = $link_aja->where('payment_method.code_name', 'pg_link_aja')->sum('sub_total') + $link_aja->where('payment_method.code_name', 'pg_link_aja')->sum('addon_total');
        $bni_va = $bni_va->where('payment_method.code_name', 'pg_va_bni')->sum('sub_total') + $bni_va->where('payment_method.code_name', 'pg_va_bni')->sum('addon_total');
        $digital = $mandiri_va + $mandiri_dd + $bri_va + $bri_dd + $link_aja + $bni_va;
        $edc = $edc->where('payment_method.code_name', 'edc')->sum('sub_total') + $edc->where('payment_method.code_name', 'edc')->sum('addon_total');

        $total_pendapatan = $total_pendapatan->sum('sub_total') + $total_addon->sum('addon_total');
        $total_addon = $total_addon->sum('addon_total');
        $investor = $data_all->whereNotNull('sharing_code')->groupBy('sharing_code')->toArray();


        // $tempInvestor = [];

        // if (count($investor) > 0) {
        //     foreach ($investor as $k => $v) {
        //         // dump($k);
        //         $arrk = json_decode($k);
        //         foreach ($arrk as $k2 => $v2) {
        //             // dump($v2);
        //             $temp = 0;
        //             foreach ($v as $k3 => $v3) {
        //                 $value = json_decode($v3['sharing_amount']);
        //                 // dump($value[$k2]);
        //                 $temp += $value[$k2];
        //             }
        //             // dump($temp);
        //             $tempInvestor[] = [$v2 => $temp];
        //         }
        //     }
        // }



        $tempInvestor = [];
        $resulttempInvestor = [];

        if (count($investor) > 0) {
            foreach ($investor as $k => $v) {
                // dump($k);
                $arrk = json_decode($k);
                foreach ($arrk as $k2 => $v2) {
                    // dump($v2);
                    $temp = 0;
                    foreach ($v as $k3 => $v3) {
                        $value = json_decode($v3['sharing_amount']);
                        // dump($value[$k2]);
                        $temp += $value[$k2];
                    }
                    // dump($temp);
                    $tempInvestor[] = [$v2 => $temp];
                }
            }
            foreach ($tempInvestor as $item) {
                foreach ($item as $key => $value) {
                    // Check if the key exists in the result array
                    if (array_key_exists($key, $resulttempInvestor)) {
                        // If the key exists, add the value to the existing sum
                        $resulttempInvestor[$key] += $value;
                    } else {
                        // If the key does not exist, create a new entry in the result array
                        $resulttempInvestor[$key] = $value;
                    }
                }
            }
        }
        $data_refund = $data->where('status','REFUND');

        $rp_refund = $data_refund->sum('sub_total') + $data_refund->sum('addon_total');

        $periode_berjalan = [
            'cashier_name' => $periode_berjalan->cashier->name ?? '',
            'start_date' => $periode_berjalan->start_date->format('Y-m-d H:i:s'),
            'periode' => $periode_berjalan->periode,
            'id' => $periode_berjalan->id,
            'initial_cashbox' => $periode_berjalan->trans_cashbox->initial_cashbox ?? 0,
            'total_cash' => $cash,
            'total_qr' => $qr,
            'total_mandiri_va' => $mandiri_va,
            'total_mandiri_dd' => $mandiri_dd,
            'total_bri_va' => $bri_va,
            'total_bri_dd' => $bri_dd,
            'total_link_aja' => $link_aja,
            'total_bni_va' => $bni_va,
            'total_digital' => $digital,
            'edc' => $edc,
            'total_addon' => $total_addon,
            'total_pendapatan' => $total_pendapatan,
            'total_refund' => $rp_refund ?? 0,
            'list_investor' => $resulttempInvestor,
            'detil' => RekapTransOrderResource::collection($data->whereIn('status',['DONE','REFUND']))
        ];

        return response()->json($periode_berjalan);
    }
}
