<?php

namespace App\Services;

use App\Http\Requests\DownloadLaporanRequest;
use App\Models\Tenant;
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
            return response()->json([
                'message' => 'Data tidak ditemukan',
            ], 400);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'data' => $data,
        ];
        return $record;
    }
}
