<?php

namespace App\Http\Controllers\API;

use App\Exports\LaporanCustomerTavqrExport;
use App\Exports\LaporanCustomerTravoyExport;
use App\Exports\LaporanInvoiceExport;
use App\Exports\LaporanJenisTransaksiExport;
use App\Exports\LaporanMetodePembayaranExport;
use App\Exports\LaporanOperationalExport;
use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanKategoriExport;
use App\Exports\LaporanProductFavoritExport;
use App\Exports\LaporanRestAreaExport;
use App\Exports\LaporanTenantExport;
use App\Exports\LaporanTransaksiExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadLaporanRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\LaporanPenjualanKategoriResource;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransInvoice;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\Voucher;
use App\Services\LaporanServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Imports\ReportImportGetoll;
use App\Imports\ReportImportLinkaja;




use Excel;

class LaporanController extends Controller
{
    public $services;
    public function __construct(LaporanServices $services)
    {
        $this->services = $services;
    }

    public function downloadLaporanPenjualan(DownloadLaporanRequest $request)
    {
        $record = $this->services->penjualan($request);
        return Excel::download(new LaporanPenjualanExport($record), 'laporan_penjualan ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function UploadRekon(Request $request)
    {
        $sof = $request->sof;
        switch ($sof) {
            case 'getoll':
                $rekon = new ReportImportGetoll($request->type);
                Excel::import($rekon, $request->file('file'));
                return response()->json($rekon->gethasil(), $rekon->gethasil()['status'] ? 200 : 400);
            case 'linkaja':
                $rekon = new ReportImportLinkaja($request->type);
                Excel::import($rekon, $request->file('file'));
                return response()->json($rekon->gethasil(), $rekon->gethasil()['status'] ? 200 : 400);
            default:
                # code...
                return response()->json(['status'=>'gagal', 402]);
        }
    }

    public function listRekon(Request $request)
    {
        $record = $this->services->listRekon($request);

        $data = [
            'Total_Transaksi' => ($record[0]->isEmpty() ? 0 : $record[0]->count('id')) + ($record[1]->isEmpty() ? 0 : $record[1]->count('id')) + ($record[2]->isEmpty() ? 0 : $record[2]->count('id')),
            'Total_Transaksi_N_Rekon' => $record[0]->isEmpty() ? 0 : $record[0]->count('id'),
            'Total_Transaksi_Rekon' => $record[1]->isEmpty() ? 0 : $record[1]->count('id'),
            'Total_Transaksi_Unmatch' => $record[2]->isEmpty() ? 0 : $record[2]->count('id'),
            'Total_Pendapatan' => ($record[0]->isEmpty() ? 0 : ($record[0]->sum('total') - $record[0]->sum('service_fee'))) + ($record[1]->isEmpty() ? 0 : ($record[1]->sum('total') - $record[1]->sum('service_fee'))) + ($record[2]->isEmpty() ? 0 : ($record[2]->sum('total') - $record[2]->sum('service_fee'))),
            'Total_Pendapatan_N_Rekon' => $record[0]->isEmpty() ? 0 : ($record[0]->sum('total') - $record[0]->sum('service_fee')),
            'Total_Pendapatan_Rekon' => $record[1]->isEmpty() ? 0 : ($record[1]->sum('total') - $record[1]->sum('service_fee')),
            'Total_Pendapatan_Unmatch' => $record[2]->isEmpty() ? 0 : ($record[2]->sum('total') - $record[2]->sum('service_fee')),
            'Data' => [
                'n_rekon' => $record[0],
                'rekon' => $record[1],
                'n_match_rekon' => $record[2],
            ]
        ];
        
        return response()->json($data);
        
    }


    public function laporanPenjualan(DownloadLaporanRequest $request)
    {
        $record = $this->services->penjualan($request);
        return response()->json($record);
    }

    public function downloadLaporanOperational(DownloadLaporanRequest $request)
    {
        $record = $this->services->operational($request);
        return Excel::download(new LaporanOperationalExport($record), 'laporan_operational ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function laporanOperational(DownloadLaporanRequest $request)
    {
        $record = $this->services->operational($request);
        return response()->json($record);
    }

    public function downloadLaporanPenjualanKategori(DownloadLaporanRequest $request)
    {
        $record = $this->services->penjualanKategori($request);
        return Excel::download(new LaporanPenjualanKategoriExport($record), 'laporan_penjualan_kategori ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function laporanPenjualanKategori(DownloadLaporanRequest $request)
    {
        $record = $this->services->penjualanKategori($request);
        return response()->json(new LaporanPenjualanKategoriResource($record));
    }

    public function downloadLaporanMetodePembayaran(DownloadLaporanRequest $request)
    {
        $record = $this->services->metodePembayaran($request);
        return Excel::download(new LaporanMetodePembayaranExport($record), 'laporan_metode_pembayaran ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function laporanMetodePembayaran(DownloadLaporanRequest $request)
    {
        $record = $this->services->metodePembayaran($request);
        return response()->json($record);
    }

    public function downloadLaporanInvoice(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $status = $request->status;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransInvoice::whereHas('trans_saldo', function ($q) use ($tenant_id, $rest_area_id, $business_id) {
            $q->when($tenant_id, function ($qq) use ($tenant_id) {
                return $qq->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->whereHas('tenant', function ($qq) use ($business_id) {
                $qq->when($business_id, function ($qqq) use ($business_id) {
                    return $qqq->where('business_id', $business_id);
                });
            });
        })->when(
                ($tanggal_awal && $tanggal_akhir),
                function ($q) use ($tanggal_awal, $tanggal_akhir) {
                    return $q->whereBetween(
                        'claim_date',
                        [
                            $tanggal_awal,
                            $tanggal_akhir . ' 23:59:59'
                        ]
                    );
                }
            )
            ->when(
                $status,
                function ($q) use ($status) {
                    return $q->where('status', $status);
                }
            )
            ->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'status' => $status,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanInvoiceExport($record), 'laporan_invoice ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanTransaksi(DownloadLaporanRequest $request)
    {
        $record = $this->services->transaksi($request);
        return Excel::download(new LaporanTransaksiExport($record), 'laporan_transaksi ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function laporanTransaksi(DownloadLaporanRequest $request)
    {
        $record = $this->services->transaksi($request);
        return response()->json($record);
    }

    public function downloadLaporanProductFavorit(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrderDetil::whereHas('trans_order', function ($q) use ($tanggal_awal, $tanggal_akhir, $tenant_id, $rest_area_id, $business_id) {
            return $q->where('status', TransOrder::DONE)
                ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                    return $qq->whereBetween(
                        'created_at',
                        [
                            $tanggal_awal,
                            $tanggal_akhir . ' 23:59:59'
                        ]
                    );
                })->when($tenant_id, function ($qq) use ($tenant_id) {
                    return $qq->where('tenant_id', $tenant_id);
                })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                    return $qq->where('rest_area_id', $rest_area_id);
                })->when($business_id, function ($qq) use ($business_id) {
                    return $qq->where('business_id', $business_id);
                });
        })->with('product.category')->get()
            ->groupBy('product.name')
            ->map(function ($item) {
                return [
                    'qty' => $item->sum('qty'),
                    'category' => $item->first()->product?->category?->name ?? '',
                    'sku' => $item->first()->product->sku ?? ''
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data->sortByDesc('qty'),
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanProductFavoritExport($record), 'laporan_product_favorit ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanJenisTransaksi(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrder::done()
            ->when(
                ($tanggal_awal && $tanggal_akhir),
                function ($q) use ($tanggal_awal, $tanggal_akhir) {
                    return $q->whereBetween(
                        'created_at',
                        [
                            $tanggal_awal,
                            $tanggal_akhir . ' 23:59:59'
                        ]
                    );
                }
            )
            ->when($tenant_id, function ($q) use ($tenant_id) {
                return $q->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->when($business_id, function ($qq) use ($business_id) {
                return $qq->where('business_id', $business_id);
            })
            ->get()
            ->groupBy('order_type')
            ->map(function ($item) {
                return [
                    'jumlah' => $item->count(),
                    'total' => $item->sum('total')
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }

        return Excel::download(new LaporanJenisTransaksiExport([
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_transaksi ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanRestArea(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;

        $data = RestArea::when(
            ($tanggal_awal && $tanggal_akhir),
            function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            }
        )
            ->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }

        return Excel::download(new LaporanRestAreaExport([
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_rest_area ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanTenant(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;

        $data = Tenant::when(
            ($tanggal_awal && $tanggal_akhir),
            function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            }
        )
            ->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }

        return Excel::download(new LaporanTenantExport([
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_tenant' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }


    public function downloadLaporanCustomerTravoy(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrder::done()
            ->when(
                ($tanggal_awal && $tanggal_akhir),
                function ($q) use ($tanggal_awal, $tanggal_akhir) {
                    return $q->whereBetween(
                        'created_at',
                        [
                            $tanggal_awal,
                            $tanggal_akhir . ' 23:59:59'
                        ]
                    );
                }
            )
            ->when($tenant_id, function ($q) use ($tenant_id) {
                return $q->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->when($business_id, function ($qq) use ($business_id) {
                return $qq->where('business_id', $business_id);
            })
            ->orderBy('customer_id')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($item, $key) {
                return [
                    'customer_id' => $key,
                    'customer_name' => $item->first()->customer_name,
                    'customer_phone' => $item->first()->customer_phone,
                    'total' => $item->count()
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }


        return Excel::download(new LaporanCustomerTravoyExport([
            'record' => $data,
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_cutomer_travoy ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanCustomerTavqr(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $rest_area_id = $request->rest_area_id;

        $data = Voucher::when(
            ($tanggal_awal && $tanggal_akhir),
            function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            }
        )
            ->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })
            ->orderBy('customer_id')
            ->get()
            ->map(function ($item, $key) {
                return [
                    'customer_id' => $item->customer_id,
                    'customer_name' => $item->nama_lengkap,
                    'customer_phone' => $item->phone,
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }


        return Excel::download(new LaporanCustomerTavqrExport([
            'record' => $data,
            'rest_area' => RestArea::where('id', $rest_area_id)->first()->name ?? 'Semua Rest Area',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_cutomer_tavqr ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }
}
