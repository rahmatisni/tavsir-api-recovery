<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Http\Resources\LaporanRekapTransaksiResource;
use App\Http\Resources\RekapResource;
use App\Http\Resources\RekapTransOrderResource;
use App\Models\TransOperational;
use App\Models\TransOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;

class LaporanRekapTransaksiController extends Controller
{
    public function index(DateRangeRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;

        DB::enableQueryLog();
        $data = TransOperational::with('trans_cashbox', 'cashier')->byRole()
            ->whereNotNull('end_date')
            ->when(($tanggal_awal && $tanggal_akhir), function ($q) use ($tanggal_awal, $tanggal_akhir) {

                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            })
            ->when($filter = request('filter'), function ($q) use ($filter) {
                return
                    $q->where('start_date', 'like', "%$filter%")
                    ->orWhere('periode', 'like', "%$filter%")
                    ->orWhere('end_date', 'like', "%$filter%")
                    ->orWhereHas('trans_cashbox', function ($query) {
                        $query->where('rp_total', 'like', "%" . request('filter') . "%");
                    });
            });

        $data = $data->orderBy('created_at', 'desc')->get();
        return response()->json(LaporanRekapTransaksiResource::collection($data));
    }

    public function showRekap($id)
    {
        $data = TransOperational::byRole()
            ->where('id', $id)
            ->whereNotNull('end_date')
            ->first();
        if (!$data) {
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }

        return response()->json(new RekapResource($data));
    }

    public function showTransaksi($id)
    {
        $periode_berjalan = TransOperational::byRole()
            ->where('id', $id)
            ->whereNotNull('end_date')
            ->first();
        if (!$periode_berjalan) {
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }

        $data_all = TransOrder::done()
            ->byRole()
            ->whereBetween('created_at', [$periode_berjalan->start_date, $periode_berjalan->end_date])
            ->when($payment_method_id = request('payment_method_id'), function ($q) use ($payment_method_id) {
                $q->where('payment_method_id', $payment_method_id);
            })
            ->when($order_type = request('order_type'), function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })
            ->orderBy('created_at', 'desc')->get();


        $data = [
            'start_date' => (string) $periode_berjalan->start_date,
            'end_date' => (string) $periode_berjalan->end_date,
            'periode' => $periode_berjalan->periode,
            'total' => $data_all->sum('total'),
            'detil' => RekapTransOrderResource::collection($data_all)
        ];

        return response()->json($data);
    }

    public function download($id)
    {
        $data = TransOperational::byRole()->where('id', $id)->whereNotNull('end_date')->first();
        $order = TransOrder::done()
            ->byRole()
            ->whereBetween('created_at', [$data->start_date, $data->end_date])
            ->when($payment_method_id = request('payment_method_id'), function ($q) use ($payment_method_id) {
                $q->where('payment_method_id', $payment_method_id);
            })
            ->when($order_type = request('order_type'), function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })
            ->get();
        if (!$data) {
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }

        $pdf = Pdf::loadView('pdf.rekap', [
            'record' => $data,
            'order' => $order
        ]);
        return $pdf->download('invoice.pdf');
    }
}
