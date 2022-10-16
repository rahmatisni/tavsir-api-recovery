<?php

namespace App\Http\Controllers\API;

use App\Exports\LaporanInvoiceExport;
use App\Exports\LaporanMetodePembayaranExport;
use App\Exports\LaporanOperationalExport;
use Illuminate\Http\Request;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanKategoriExport;
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
        $data = TransOrderDetil::get();
        
        return Excel::download(new LaporanPenjualanExport($data), 'laporan_penjualan '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanOperational()
    {
        $data = TransOperational::get();

        return Excel::download(new LaporanOperationalExport($data), 'laporan_operational '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanPenjualanKategori()
    {
        $data = TransOrderDetil::whereHas('trans_order', function($q){
            return $q->where('status', TransOrder::DONE);
        })->with('product.category')->get()
        ->groupBy('product.category.name')
        ->map(function($item){
            return [
                    'qty' => $item->sum('qty'),
                    'total' => $item->sum('total_price')
                ];
        });
        return Excel::download(new LaporanPenjualanKategoriExport($data), 'laporan_penjualan_kategori '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanMetodePembayaran()
    {
        $data = TransOrder::Done()->with('payment_method')->get()
        ->groupBy('payment_method.name')
        ->map(function($item){
            return [
                    'qty' => $item->count(),
                    'total' => $item->sum('total')
                ];
        });
        return Excel::download(new LaporanMetodePembayaranExport($data), 'laporan_metode_pembayaran '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

    public function downloadLaporanInvoice()
    {
        $data = TransInvoice::get();

        return Excel::download(new LaporanInvoiceExport($data), 'laporan_invoice '.Carbon::now()->format('d-m-Y').'.xlsx');
    }

}
