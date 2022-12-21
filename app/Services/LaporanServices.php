<?php

namespace App\Services;

use App\Http\Requests\DownloadLaporanRequest;
use App\Http\Resources\LaporanOperationalResource;
use App\Models\Tenant;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;

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
            ->groupBy('product.category.name')
            ->map(function ($item) {
                return [
                    'qty' => $item->sum('qty'),
                    'total' => $item->sum('total_price')
                ];
            });
        if ($data->count() == 0) {
            abort(404);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'data' => $data,
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
            'record' => LaporanOperationalResource::collection($data),
            'total_qr' => $data->sum('trans_cashbox.rp_tav_qr'),
            'total_digital' => $data->sum('trans_cashbox.total_digital'),
            'total_tunai' => $data->sum('trans_cashbox.rp_cash'),
            'total_nominal_tunai' => $data->sum('trans_cashbox.cashbox'),
            'total_koreksi' => $data->sum('trans_cashbox.pengeluaran_cashbox'),
            'total' => $data->sum('trans_cashbox.rp_total'),
        ];
        return $record;
    }
}
