<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Models\Product;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;

        $order = TransOrder::Done()
            ->when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
                $q->whereHas('tenant', function ($qq) use ($rest_area_id) {
                    $qq->where('rest_area_id', $rest_area_id);
                });
            })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
                $q->where('tenant_id', $tenant_id);
            })->when($business_id = $request->business_id, function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->when(($tanggal_awal && $tanggal_akhir), function ($q) use ($tanggal_awal, $tanggal_akhir) {

                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            })->get();
        $all1 = $order;
        $all2 = $order;
        $all3 = $order;
        $takengo_count = $all2->where('order_type', TransOrder::ORDER_TAKE_N_GO)->count();
        $tavsir = $all3->where('order_type', TransOrder::ORDER_TAVSIR)->count();

        $rest_area = RestArea::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
            $q->where('id', $rest_area_id);
        })->get();

        $tenant = Tenant::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
            $q->where('rest_area_id', $rest_area_id);
        })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
            $q->where('id', $tenant_id);
        })->get();

        $customer_count = 0;
        if(auth()->user()->role == User::JMRBAREA)
        {
            $customer_count = Voucher::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
                        $q->where('rest_area_id', $rest_area_id);
                    })->count();
        }

        if(auth()->user()->role == User::TENANT)
        {
            $customer_count = $order->whereNotNull('customer_id')->unique('customer_id')->count();
        }
       

        $total_pemasukan = $all1->sum('sub_total');
        $total_transaksi_takengo = $takengo_count;
        $total_transaksi_tavsir = $tavsir;
        $total_transaksi = $total_transaksi_tavsir + $total_transaksi_takengo;
        $total_rest_area = $rest_area->count();
        $total_merchant = 0;
        $total_tenant = $tenant->count();
        $total_customer = $customer_count;

        $ct = $order;
        $ct_group = $ct->sortBy('tenant.category_tenant_id')
            ->groupBy('tenant.category_tenant_id')->map(function ($item) {
                return $item->count();
            });
        $category_tenant = [
            'labels' => array_keys($ct_group->toArray()),
            'data' => array_values($ct_group->toArray())
        ];

        // $category_tenant = [
        //     'labels' => Tenant::categoryCount()->pluck('kategori'),
        //     'data' => Tenant::categoryCount()->pluck('tenant')
        // ];

        $pm = $order;
        $grouped = $pm->sortBy('payment_method.name')
            ->groupBy('payment_method.name')->map(function ($item) {
                return $item->count();
            });
        $payment_method = [
            'labels' => array_keys($grouped->toArray()),
            'data' => array_values($grouped->toArray())
        ];

        $topRest = $order;
        $topRest = $topRest->groupBy('rest_area_id')->map(function ($item) {
            return $item->count();
        })->sortDesc();
        $top_rest_area = [];
        foreach ($topRest as $key => $value) {
            $restarea = RestArea::find($key);
            $top_rest_area[] = [
                'name' => $restarea->name ?? '',
                'photo' => $restarea->photo ? asset($restarea->photo) : null,
                'total_transaksi' => $value,
            ];
        }

        // $top_rest_area = [
        //     [
        //         'name' => 'Rumah Rest Area KM 10 A Jagorawi',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 200,
        //     ],
        //     [
        //         'name' => 'Rest Area KM 35 A Jagorawi',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 190,
        //     ],
        //     [
        //         'name' => 'Rest Area KM 44 A Jagorawi',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 184,
        //     ]
        // ];

        $topTenant = $order;
        $topTenant = $topTenant->groupBy('tenant_id')->map(function ($item) {
            return $item->count();
        })->sortDesc();
        $top_tenant = [];
        foreach ($topTenant as $key => $value) {
            $tenant = Tenant::find($key);
            $top_tenant[] = [
                'name' => $tenant->name,
                'photo' => $tenant->photo ? asset($tenant->photo) : null,
                'total_transaksi' => $value,
            ];
        }

        // $top_tenant = [
        //     [
        //         'name' => 'Rumah Talas',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 200,
        //     ],
        //     [
        //         'name' => 'Starbucks',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 190,
        //     ],
        //     [
        //         'name' => 'MCD',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 184,
        //     ],
        //     [
        //         'name' => 'KFC',
        //         'photo' => 'https://via.placeholder.com/50',
        //         'total_transaksi' => 179,
        //     ]
        // ];

        $topProduct = $order;
        $topProduct = $topProduct->groupBy('detil.product_id')->map(function ($item) {
                return $item->count();
            })->sortDesc();
        $top_product = [];
        foreach ($topProduct as $key => $value) {
            $product = Product::find($key);
            $top_product[] = [
                'name' => $product->name ?? '',
                'photo' => $product?->photo ? asset($product?->photo) : null,
                'total' => $value,
            ];
        }

        $data = [
            'total_pemasukan' => number_format($total_pemasukan, 0, ',', '.'),
            'total_transaksi_tavsir' => number_format($total_transaksi_tavsir, 0, ',', '.'),
            'total_transaksi_tng' => number_format($total_transaksi_takengo, 0, ',', '.'),
            'total_transaksi' => number_format($total_transaksi, 0, ',', '.'),
            'total_rest_area' => $total_rest_area,
            'total_tenant' => $total_tenant,
            'total_customer' => $total_customer,
            'category_tenant' => $category_tenant,
            'payment_method' => $payment_method,
            'top_rest_area' => $top_rest_area,
            'top_tenant' => $top_tenant,
            'total_merchat' => $total_merchant,
            'top_product' => $top_product,
        ];

        return response()->json($data);
    }
}
