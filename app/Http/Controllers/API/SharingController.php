<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SharingRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\SharingIndexResource;
use App\Http\Resources\SharingShowResource;
use App\Models\Sharing;
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
            )->get();
        return response()->json(SharingIndexResource::collection($data));
    }

    public function cek_status($start, $end, $id)
    {
        if ($start > date((Carbon::now()->format('Y-m-d H:i:s')))) {
            return 'belum_berjalan';
        }
    }
    public function store(SharingRequest $request)
    {
        // 'status' => 'required|in:sedang_berjalan,belum_berjalan,sudah_berakhir',
        try {
            $validator = Sharing::where('tenant_id', $request->tenant_id)->get();
            foreach ($validator as $value) {

                if ($value->waktu_selesai > $request->waktu_selesai) {
                    return response()->json(['status' => "error", 'message' => "Terdapat PKS yang masih berlaku"], 422);
                }
                if ($value->waktu_mulai < $request->waktu_mulai && $request->waktu_mulai > date((Carbon::now()->format('Y-m-d H:i:s')))){
                    $value->update(['status' => 'sudah_berakhir']);
                }
            }

            DB::beginTransaction();
            $data = new Sharing();
            $data->fill($request->all());
            $data->sharing_code = $request->sharing_code;
            $data->sharing_config = $request->sharing_config;
            $data->status = $this->cek_status($request->waktu_mulai, $request->waktu_selesai, $request->tenant_id);
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
