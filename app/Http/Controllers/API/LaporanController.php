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
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransInvoice;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\Voucher;
use Carbon\Carbon;
use Excel;

class LaporanController extends Controller
{
    public function downloadLaporanPenjualan(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrderDetil::whereHas(
            'trans_order',
            function ($q) use ($tanggal_awal, $tanggal_akhir, $tenant_id, $rest_area_id, $business_id) {
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
            }
        )->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanPenjualanExport($record), 'laporan_penjualan ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanOperational(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOperational::when(($tanggal_awal && $tanggal_akhir), function ($q) use ($tanggal_awal, $tanggal_akhir) {
            return $q->whereBetween(
                'created_at',
                [
                    $tanggal_awal,
                    $tanggal_akhir . ' 23:59:59'
                ]
            );
        })->whereHas('tenant', function ($qq) use ($tenant_id, $rest_area_id, $business_id) {
            return $qq->when($tenant_id, function ($qq) use ($tenant_id) {
                return $qq->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->when($business_id, function ($qq) use ($business_id) {
                return $qq->where('business_id', $business_id);
            });
        })
            ->whereNotNull('end_date')
            ->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanOperationalExport($record), 'laporan_operational ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanPenjualanKategori(DownloadLaporanRequest $request)
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
            ->groupBy('product.category.name')
            ->map(function ($item) {
                return [
                    'qty' => $item->sum('qty'),
                    'total' => $item->sum('total_price')
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanPenjualanKategoriExport($record), 'laporan_penjualan_kategori ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

    public function downloadLaporanMetodePembayaran(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrder::Done()->when(($tanggal_awal && $tanggal_akhir),
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
            ->with('payment_method')->get()
            ->groupBy('payment_method.name')
            ->map(function ($item) {
                return [
                    'qty' => $item->count(),
                    'total' => $item->sum('total')
                ];
            });
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ];
        return Excel::download(new LaporanMetodePembayaranExport($record), 'laporan_metode_pembayaran ' . Carbon::now()->format('d-m-Y') . '.xlsx');
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
        })->when(($tanggal_awal && $tanggal_akhir),
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
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrder::done()
            ->when(($tanggal_awal && $tanggal_akhir),
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
            ->orderBy('created_at')
            ->get();
        if ($data->count() == 0) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }

        return Excel::download(new LaporanTransaksiExport([
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'record' => $data,
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
        ]), 'laporan_transaksi ' . Carbon::now()->format('d-m-Y') . '.xlsx');
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
                if (!$item->first()->product) {
                    dd($item);
                }
                return [
                    'qty' => $item->sum('qty'),
                    'category' => $item->first()->product->category->name ?? '',
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
            ->when(($tanggal_awal && $tanggal_akhir),
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

        $data = RestArea::when(($tanggal_awal && $tanggal_akhir),
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

        $data = Tenant::when(($tanggal_awal && $tanggal_akhir),
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
            ->when(($tanggal_awal && $tanggal_akhir),
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

        $data = Voucher::when(($tanggal_awal && $tanggal_akhir),
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
