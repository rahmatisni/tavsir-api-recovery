<?php

namespace App\Services;

use App\Exports\LaporanTransaksiExport;
use App\Http\Requests\DownloadLaporanRequest;
use App\Http\Resources\LaporanMetodePembayaranResource;
use App\Http\Resources\LaporanOperationalResource;
use App\Http\Resources\LaporanPenjualanResource;
use App\Http\Resources\LaporanTransaksiResource;
use App\Models\Tenant;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use Carbon\Carbon;

class LaporanServices
{
    public function penjualanKategori(DownloadLaporanRequest $request)
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
            ->groupBy('product.category.name');

        $hasil = [];
        $sum_total_transaksi = 0;
        $sum_jumlah_transaksi = 0;
        foreach ($data as $k => $i) {
            $jumlah_transaksi = $i->sum('qty');
            $total_transaksi = $i->sum('total_price');

            $sum_jumlah_transaksi += $jumlah_transaksi;
            $sum_total_transaksi += $total_transaksi;

            array_push($hasil, [
                'kategori' => $k,
                'jumlah_terjual' => $jumlah_transaksi,
                'pendapatan_kategori' => $total_transaksi,
            ]);
        }

        if ($data->count() == 0) {
            abort(404);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'sum_jumlah_transaksi' => $sum_jumlah_transaksi,
            'sum_total_transaksi' => $sum_total_transaksi,
            'data' => $hasil,
        ];
        return $record;
    }

    public function operational(DownloadLaporanRequest $request)
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
            abort(404);
        }

        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_qr' => $data->sum('trans_cashbox.rp_tav_qr'),
            'total_digital' => $data->sum('trans_cashbox.total_digital'),
            'total_tunai' => $data->sum('trans_cashbox.rp_cash'),
            'total_nominal_tunai' => $data->sum('trans_cashbox.cashbox'),
            'total_koreksi' => $data->sum('trans_cashbox.pengeluaran_cashbox'),
            'total' => $data->sum('trans_cashbox.rp_total'),
            'record' => json_decode(LaporanOperationalResource::collection($data)->toJson()),
        ];
        return $record;
    }

    public function penjualan(DownloadLaporanRequest $request)
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
            abort(404);
        }
        $res = json_decode(LaporanPenjualanResource::collection($data)->toJson());
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_jumlah' => $data->sum('qty'),
            'total_pendapatan' => $data->sum('total_price'),
            'record' => $res
        ];
        return $record;
    }

    public function metodePembayaran(DownloadLaporanRequest $request)
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
            ->groupBy('payment_method.name');

        $hasil = [];
        $sum_total_transaksi = 0;
        $sum_jumlah_transaksi = 0;
        foreach ($data as $k => $i) {
            $jumlah_transaksi = $i->count();
            $total_transaksi = $i->sum('total');

            $sum_jumlah_transaksi += $jumlah_transaksi;
            $sum_total_transaksi += $total_transaksi;

            array_push($hasil, [
                'metode' => $k,
                'jumlah_transaksi' => $jumlah_transaksi,
                'total_transaksi' => $total_transaksi
            ]);
        }
        if ($data->count() == 0) {
            abort(404);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'sum_jumlah_transaksi' => $sum_jumlah_transaksi,
            'sum_total_transaksi' => $sum_total_transaksi,
            'record' => $hasil,
        ];

        return $record;
    }

    public function transaksi(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;
        $order_type = $request->order_type;
        $payment_method_id = $request->payment_method_id;

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
            })->when($order_type, function ($qq) use ($order_type) {
                return $qq->where('order_type', $order_type);
            })->when($payment_method_id, function ($qq) use ($payment_method_id) {
                return $qq->where('payment_method_id', $payment_method_id);
            })
            ->orderBy('created_at')
            ->get();
        if ($data->count() == 0) {
            abort(404);
        }

        $item_count = 0;
        $hasil = [];

        foreach ($data as $value) {
            $count = $value->detil->count();
            $item_count += $count;

            array_push($hasil, [
                'waktu_transaksi' => (string) $value->created_at,
                'id_transaksi' => $value->order_id,
                'total_product' => $count,
                'total' => $value->total,
                'metode_pembayaran' => $value->payment_method->name ?? '',
                'jenis_transaksi' => $value->labelOrderType()
            ]);
        }

        $data = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_product' => $item_count,
            'total_total' => $data->sum('sub_total'),
            'record' => $hasil,

        ];

        return $data;
    }
}
