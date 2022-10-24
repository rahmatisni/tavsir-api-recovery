<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ListInvoiceResource;
use App\Models\TransInvoice;
use App\Models\TransSaldo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        $data = TransSaldo::with('trans_invoice')->ByRole()
        ->when($rest_area_id = request()->rest_area_id, function($query) use ($rest_area_id){
            return $query->where('rest_area_id', $rest_area_id);
        })
        ->when($tenant_id = request()->tenant_id, function($query) use ($tenant_id){
            return $query->where('tenant_id', $tenant_id);
        })
        ->get();
        return response()->json(ListInvoiceResource::collection($data));
    } 
    
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = TransSaldo::with('trans_invoice')->ByTenant()->first();
            if(!$data){
                return response()->json(['message' => 'Saldo tidak ditemukan'], 404);
            }
            
            if($data->saldo < $request->nominal){
                return response()->json(['message' => 'Saldo tidak mencukupi'], 400);
            }
            
            $invoice = new TransInvoice();
            $invoice->invoice_id = 'INV-'.strtolower(Str::random(16));
            $invoice->nominal = $request->nominal;
            $invoice->cashier_id = auth()->user()->id;
            $invoice->claim_date = Carbon::now();

            $data->trans_invoice()->save($invoice);
            $data->saldo = $data->saldo - $request->nominal;
            $data->save();

            DB::commit();
            
            return response()->json($invoice);
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json($th->getMessage(),500);
        }
    }

    public function paid(Request $request, $id)
    {
        $data = TransInvoice::findOrfail($id);
        if($data->status == TransInvoice::PAID){
            return response()->json(['message' => 'Invoice sudah dibayar'], 400);
        }
        $data->status = TransInvoice::PAID;
        $data->pay_station_id = $request->pay_station_id ?? auth()->user()->id;
        $data->paid_date = Carbon::now();
        $data->kwitansi_id = strtolower(Str::random(16));
        $data->save();

        return response()->json($data);
    }

    public function show()
    {
        $data = TransInvoice::findOrfail(request()->id);
        
        return response()->json(new InvoiceResource($data));
    }

}
