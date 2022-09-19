<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $order = TransOrder::Done()
                            ->when($rest_area_id = request()->rest_area_id, function($q) use ($rest_area_id){
                                $q->whereHas('tenant', function($qq) use ($rest_area_id){
                                    $qq->where('rest_area_id', $rest_area_id);
                                });
                            })->when($tenant_id = request()->tenant_id, function($q) use ($tenant_id){
                                $q->where('tenant_id', $tenant_id);
                            })->when($business_id = request()->business_id, function($q) use ($business_id){
                                $q->where('business_id', $business_id);
                            })->when($tanggal = request()->tanggal, function($q) use ($tanggal){
                                $q->whereDate('created_at', $tanggal);
                            })->get();
        $all1 = $order;
        $all2 = $order;
        $all3 = $order;
        $takengo_count = $all2->where('order_type', TransOrder::ORDER_TAKE_N_GO)->count();
        $tavsir = $all3->where('order_type', TransOrder::ORDER_TAVSIR)->count();
       
        $rest_area = RestArea::get();

        $tenant = Tenant::all();

        $voucher = Voucher::get();
        
        $total_pemasukan = $all1->sum('total');
        $total_transaksi_takengo = $takengo_count;
        $total_transaksi_tavsir = $tavsir;
        $total_transaksi = $total_transaksi_tavsir + $total_transaksi_takengo;
        $total_rest_area = $rest_area->count();
        $total_merchat = 100;
        $total_tenant = $tenant->count();
        $total_customer = $voucher->count();
        $category_tenant = [
            'labels' => Tenant::categoryCount()->pluck('kategori'),
            'data' => Tenant::categoryCount()->pluck('tenant')
        ];
        $payment_method = [
            'labels' => TransOrder::paymentMethodCount()->get(),
            'data' => TransOrder::paymentMethodCount()->pluck('total')
        ];

        $top_rest_area = [
            [
                'name' => 'Rumah Rest Area KM 10 A Jagorawi',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 200,
            ],
            [
                'name' => 'Rest Area KM 35 A Jagorawi',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 190,
            ],
            [
                'name' => 'Rest Area KM 44 A Jagorawi',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 184,
            ]
        ];

        // $top_rest_area = TransOrder::with(['tenant'], function($q){
        //     return $q->groupBy('rest_area_id')->select('rest_area_id', DB::raw('COUNT(*) as total'));
        // })->get();

        $top_tenant = [
            [
                'name' => 'Rumah Talas',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 200,
            ],
            [
                'name' => 'Starbucks',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 190,
            ],
            [
                'name' => 'MCD',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 184,
            ],
            [
                'name' => 'KFC',
                'photo' => 'https://via.placeholder.com/50',
                'total_transaksi' => 179,
            ]
        ];
        $data = [
            'total_pemasukan' => number_format($total_pemasukan,0,',','.'),
            'total_transaksi_tavsir' => number_format($total_transaksi_tavsir,0,',','.'),
            'total_transaksi_tng' => number_format($total_transaksi_takengo,0,',','.'),
            'total_transaksi' => number_format($total_transaksi,0,',','.'),
            'total_rest_area' => $total_rest_area,
            'total_merchat' => $total_merchat,
            'total_tenant' => $total_tenant,
            'total_customer' => $total_customer,
            'category_tenant' => $category_tenant,
            'payment_method' => $payment_method,
            'top_rest_area' => $top_rest_area,
            'top_tenant' => $top_tenant,
        ];

        return response()->json($data);
    }
}
