<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SharingRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\SharingIndexResource;
use App\Http\Resources\SharingShowResource;
use App\Models\Sharing;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class SharingController extends Controller
{
    public function index()
    {
        $waktu_mulai = request()->waktu_mulai;
        $waktu_selesai = request()->waktu_selesai;

        $data = Sharing::when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when(
                ($waktu_mulai && $waktu_selesai),
                function ($q) use ($waktu_mulai, $waktu_selesai) {
                    return $q->where('waktu_mulai', '>=', $waktu_mulai)
                        ->where('waktu_selesai', '<=', $waktu_selesai);
                }
            )->when($business_id = request()->business_id, function ($q) use ($business_id) {
                return $q->where('business_id', $business_id);
            })->when($nomor_pks = request()->nomor_pks, function ($q) use ($nomor_pks) {
            return $q->where('nomor_pks', 'like', "%$nomor_pks%");
        })->when($status = request()->status, function ($q) use ($status) {
            // return $q->where('status', $status);
            $now = Carbon::now()->format('Y-m-d H:i:s');

            switch ($status) {
                case 'belum_berjalan':
                    // Code to execute if expression matches value1
                    return $q
                        // ->where('status', $status)
                        ->where('waktu_mulai', '>=', $now);
                    // ->where('waktu_selesai', '>=', $now);


                    break;

                case 'sudah_berakhir':
                    // Code to execute if expression matches value2
                    return $q
                        // ->where('status', $status)
                        ->where('waktu_selesai', '<', $now);
                    break;

                case 'sedang_berjalan':
                    // Code to execute if expression matches value2
                    // return $q->where('status', $status)
                    return $q
                        ->where('waktu_mulai', '<=', $now)
                        ->where('waktu_selesai', '>=', $now);
                    break;

                // Add more cases as needed

                default:
                    // Code to execute if none of the cases match the expression
                    return $q->where('status', $status);
                    break;
            }

        })->get();

        $collectionWithNewKey = $data->map(function ($item) {
        $item['status'] = $this->cek_status($item['waktu_mulai'], $item['waktu_selesai'], $item['tenant_id']);
        $item['status_code'] = $this->cek_status($item['waktu_mulai'], $item['waktu_selesai'], $item['tenant_id']) === 'sedang_berjalan' ? 1 : ($this->cek_status($item['waktu_mulai'], $item['waktu_selesai'], $item['tenant_id']) === 'sudah_berakhir' ? 3:2);
        return $item;
        });
        
        $sortedCollection = $collectionWithNewKey->sortBy('status_code');

        return response()->json(SharingIndexResource::collection($sortedCollection));
    }

    public function cek_status($start, $end, $id)
    {
        if (Carbon::now()->format('Y-m-d H:i:s') < $start) {
            return 'belum_berjalan';

        } elseif (Carbon::now()->format('Y-m-d H:i:s') >= $start && Carbon::now()->format('Y-m-d H:i:s') <= $end) {
            return 'sedang_berjalan';

        } else {
           return 'sudah_berakhir';
        }
        
    }
    public function store(SharingRequest $request)
    {

        $tenant = Tenant::find($request->tenant_id);
        try {
            $amount = array_sum(json_decode($request->sharing_config));
            if ($amount !== 100) {
                return response()->json(['status' => "error", 'message' => "Proposi tidak sesuai"], 422);
            }
            $validator = Sharing::where('tenant_id', $request->tenant_id)->get();
            foreach ($validator as $value) {

                if ($value->waktu_selesai > $request->waktu_selesai) {
                    return response()->json(['status' => "error", 'message' => "Terdapat PKS yang masih berlaku"], 422);
                }
                if ($value->waktu_mulai < $request->waktu_mulai && $request->waktu_mulai > date((Carbon::now()->format('Y-m-d H:i:s')))) {
                    $value->update(['status' => 'sudah_berakhir']);
                }
            }


            DB::beginTransaction();
            $data = new Sharing();
            $data->fill($request->all());
            $data->business_id = $tenant->business_id;
            $data->sharing_code = json_decode($request->sharing_code) ?? $request->sharing_code;
            $data->sharing_config = json_decode($request->sharing_config) ?? $request->sharing_config;
            $data->status = $this->cek_status($request->waktu_mulai, $request->waktu_selesai, $request->tenant_id);
            $data->deskripsi = $request->deskripsi;
            $data->save();
            DB::commit();
            return response()->json(new SharingShowResource($data));
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $sharing = Sharing::with('tenant')->findOrFail($id);
        $sharing->status = $this->cek_status($sharing['waktu_mulai'], $sharing['waktu_selesai'], $sharing['tenant_id']);

        return response()->json(new SharingShowResource($sharing));
    }

    public function update(SharingRequest $request, $id)
    {
        try {
            $sharing = Sharing::findOrFail($id);
            DB::beginTransaction();
            $sharing->fill($request->all());
            $sharing->save();
            DB::commit();
            return response()->json($sharing);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $sharing = Sharing::findOrFail($id);
        $sharing->delete();
        return response()->noContent();
    }
}
