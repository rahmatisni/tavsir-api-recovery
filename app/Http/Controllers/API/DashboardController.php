<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $total_pemasukan = 50000000;
        $total_transaksi_tavsir = 100;
        $total_transaksi_takengo = 100;
        $total_transaksi = $total_transaksi_tavsir + $total_transaksi_takengo;
        $total_rest_area = 10;
        $total_merchat = 100;
        $total_tenant = 200;
        $total_customer = 100;
        $category_tenant = [
            'labels' => ['Food','Market','Fashion'],
            'data' => [20,10,15]
        ];
        $payment_method = [
            'labels' => ['Tunai','TAVQR','Pembayaran Digital'],
            'data' => [60,30,10]
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
            'total_transaksi_tng' => number_format($total_transaksi_tavsir,0,',','.'),
            'total_transaksi' => number_format($total_transaksi,0,',','.'),
            'total_rest_area' => $total_rest_area,
            'total_merchat' => $total_merchat,
            'total_tenant' => $total_tenant,
            'total_customer' => $total_customer,
            'category_tenant' => $category_tenant,
            'top_rest_area' => $top_rest_area,
            'top_tenant' => $top_tenant,
        ];

        return response()->json($data);
    }
}
