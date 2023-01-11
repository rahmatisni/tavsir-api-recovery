<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SharingRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\SharingShowResource;
use App\Models\Sharing;
use Illuminate\Support\Facades\DB;

class SharingController extends Controller
{
    public function index()
    {
        $waktu_mulai = request()->waktu_mulai;
        $waktu_selesai = request()->waktu_selesai;

        $data = Sharing::when($pengelola_id = request()->pengelola_id, function ($q) use ($pengelola_id) {
            return $q->where('pengelola_id', $pengelola_id);
        })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when(($waktu_mulai && $waktu_selesai),
            function ($q) use ($waktu_mulai, $waktu_selesai) {
                return $q->where('waktu_mulai', '>=', $waktu_mulai)
                    ->where('waktu_selesai', '<=', $waktu_selesai);
            }
        )->get();
        return response()->json(BaseResource::collection($data));
    }

    public function store(SharingRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new Sharing();
            $data->fill($request->all());
            $data->save();
            DB::commit();
            return response()->json($data);
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
