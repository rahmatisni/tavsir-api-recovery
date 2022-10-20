<?php

namespace App\Http\Controllers\API;

use App\Exports\LaporanInvoiceExport;
use App\Exports\LaporanMetodePembayaranExport;
use App\Exports\LaporanOperationalExport;
use Illuminate\Http\Request;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanKategoriExport;
use App\Exports\LaporanTransaksiExport;
use App\Http\Controllers\Controller;
use App\Models\TransInvoice;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use Carbon\Carbon;
use Excel;

class LaporanController extends Controller
{
    public function downloadLaporanPenjualan()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;
        
        $data = TransOrderDetil::whereHas('trans_order', function($q) use ($tanggal_awal, $tanggal_akhir)
                                                        {
                                                            return $q->where('status', TransOrder::DONE)
                                                                ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                                                                    return $qq->whereBetween(
                                                                        'created_at',
                                                                        [
                                                                            $tanggal_awal,
                                                                            $tanggal_akhir.' 23:59:59'
                                                                        ]
                                                                    );
                                                                });
                                                        }
                                                    )->get();
        $record = [
            'record' => $data,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ];
        return Excel::download(new LaporanPenjualanExport($record), 'laporan_penjualan '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanOperational()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;

        $data = TransOperational::when(($tanggal_awal && $tanggal_akhir), function($q) use ($tanggal_awal, $tanggal_akhir){
                                    return $q->whereBetween('created_at', 
                                            [
                                                $tanggal_awal, 
                                                $tanggal_akhir.' 23:59:59'
                                            ]);
                                })
                                ->get();
        $record = [
            'record' => $data,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ];
        return Excel::download(new LaporanOperationalExport($record), 'laporan_operational '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanPenjualanKategori()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;

        $data = TransOrderDetil::whereHas('trans_order', function($q) use ($tanggal_awal, $tanggal_akhir){
            return $q->where('status', TransOrder::DONE)
                        ->when(($tanggal_awal && $tanggal_akhir), function($qq) use ($tanggal_awal, $tanggal_akhir){
                            return $qq->whereBetween('created_at', 
                                    [
                                        $tanggal_awal, 
                                        $tanggal_akhir.' 23:59:59'
                                    ]);
            });
        })->with('product.category')->get()
        ->groupBy('product.category.name')
        ->map(function($item){
            return [
                    'qty' => $item->sum('qty'),
                    'total' => $item->sum('total_price')
                ];
        });
        $record = [
            'record' => $data,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ];
        return Excel::download(new LaporanPenjualanKategoriExport($record), 'laporan_penjualan_kategori '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanMetodePembayaran()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;

        $data = TransOrder::Done()
                            ->when(($tanggal_awal && $tanggal_akhir), 
                                    function($q) use ($tanggal_awal, $tanggal_akhir)
                                    {
                                        return $q->whereBetween('created_at', 
                                                [
                                                    $tanggal_awal, 
                                                    $tanggal_akhir.' 23:59:59'
                                                ]);
                                    })
                            ->with('payment_method')->get()
                            ->groupBy('payment_method.name')
                            ->map(function($item){
                                return [
                                        'qty' => $item->count(),
                                        'total' => $item->sum('total')
                                    ];
                            });
        $record = [
            'record' => $data,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ];
        return Excel::download(new LaporanMetodePembayaranExport($record), 'laporan_metode_pembayaran '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanInvoice()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;
        $status = request()->status;
        $data = TransInvoice::when(($tanggal_awal && $tanggal_akhir), 
                function($q) use ($tanggal_awal, $tanggal_akhir)
                {
                    return $q->whereBetween('claim_date', 
                            [
                                $tanggal_awal, 
                                $tanggal_akhir.' 23:59:59'
                            ]);
                })
                ->when($status, 
                        function($q) use ($status)
                        {
                            return $q->where('status', $status);
                        })
                ->get();
        $record = [
            'record' => $data,
            'status' => $status,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ];
        return Excel::download(new LaporanInvoiceExport($record), 'laporan_invoice '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanTransaksi()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;
        
        $data = TransOrder::done()
                                ->when(($tanggal_awal && $tanggal_akhir), 
                                    function($q) use ($tanggal_awal, $tanggal_akhir)
                                    {
                                        return $q->whereBetween('created_at', 
                                                [
                                                    $tanggal_awal, 
                                                    $tanggal_akhir.' 23:59:59'
                                                ]);
                                    })
                                ->get();
                              
        return Excel::download(new LaporanTransaksiExport([
            'record' => $data,
            'tanggal_awal' => $tanggal_awal,
            'tanggal_akhir' => $tanggal_akhir,
        ]), 'laporan_transaksi '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

}
