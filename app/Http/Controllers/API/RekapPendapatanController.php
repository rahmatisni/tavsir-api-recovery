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

        $data_all = TransOrder::done()
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->where('casheer_id', auth()->user()->id)
                            ->whereBetween('created_at', [$periode_berjalan->start_date, Carbon::now()])
                            ->get();
        $cash = $data_all;
        $qr = $data_all;
        $digital = $data_all;
        $total_pendapatan = $data_all;

        $cash = $cash->where('payment_method_id', 6)->sum('total');
        $qr = $qr->where('payment_method_id', 5)->sum('total');
        $digital = $digital->whereIn('payment_method_id', [1,2,3,4,7])->sum('total');
        $total_pendapatan = $total_pendapatan->sum('total');
        
        $periode_berjalan = $periode_berjalan;
        $periode_berjalan = [
            'cashier_name' => $periode_berjalan->cashier->name ?? '',
            'start_date' => $periode_berjalan->start_date->format('Y-m-d H:i:s'),
            'periode' => $periode_berjalan->periode,
            'total_cash' => $cash,
            'total_qr' => $qr,
            'total_digital' => $digital,
            'total_pendapatan' => $total_pendapatan,
            'detil' => RekapTransOrderResource::collection($data_all)
        ];

        return response()->json($periode_berjalan);
    }
}
