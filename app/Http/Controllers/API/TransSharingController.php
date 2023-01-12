<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\TransSharing;

class TransSharingController extends Controller
{
    public function index()
    {
        $tanggal_awal = request()->tanggal_awal;
        $tanggal_akhir = request()->tanggal_akhir;

        $data = TransSharing::byRole()->when($pengelola_id = request()->pengelola_id, function ($q) use ($pengelola_id) {
            return $q->where('pengelola_id', $pengelola_id);
        })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when($supertenant_id = request()->supertenant_id, function ($q) use ($supertenant_id) {
            return $q->where('supertenant_id', $supertenant_id);
        })->when(($tanggal_awal && $tanggal_akhir),
            function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            }
        )->get();
        return response()->json(BaseResource::collection($data));
    }

    public function show($id)
    {
        $sharing = TransSharing::byRole()->findOrFail($id);
        return response()->json(new BaseResource($sharing));
    }
}
