<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanRekapTransaksiResource;
use App\Http\Resources\RekapResource;
use App\Http\Resources\RekapTransOrderResource;
use App\Models\TransOperational;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanRekapTransaksiController extends Controller
{
    public function index()
    {
        $data = TransOperational::with('trans_cashbox', 'cashier')
                                ->where('tenant_id', auth()->user()->tenant_id)
                                ->where('casheer_id', auth()->user()->id)
                                ->when($tanggal = request('tanggal'), function($q) use ($tanggal){
                                    $q->whereDate('created_at', $tanggal);
                                })
                                ->get();

        return response()->json(LaporanRekapTransaksiResource::collection($data));                     
    }

    public function showRekap($id)
    {
        $data = TransOperational::where('id',$id)->whereNotNull('end_date')->first();
        if(!$data){
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }

        return response()->json(new RekapResource($data));

    }

    public function showTransaksi($id)
    {
        $periode_berjalan = TransOperational::where('id',$id)->whereNotNull('end_date')->first();
        if(!$periode_berjalan){
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }

        $data_all = TransOrder::done()
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->where('casheer_id', auth()->user()->id)
                            ->whereBetween('created_at', [$periode_berjalan->start_date, $periode_berjalan->end_date])
                            ->when($payment_method_id = request('payment_method_id'), function($q) use ($payment_method_id){
                                $q->where('payment_method_id', $payment_method_id);
                            })
                            ->when($order_type = request('order_type'), function($q) use ($order_type){
                                $q->where('order_type', $order_type);
                            })
                            ->get();
       
        
        $data = [
            'start_date' => (string) $periode_berjalan->start_date,
            'end_date' => (string) $periode_berjalan->end_date,
            'periode' => $periode_berjalan->periode,
            'detil' => RekapTransOrderResource::collection($data_all)
        ];

        return response()->json($data);                     
    }
}
