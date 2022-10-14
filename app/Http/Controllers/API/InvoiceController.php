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
        $data = TransSaldo::with('trans_invoice')->ByTenant()->first();
        if(!$data){
            $data = TransSaldo::create([
                'rest_area_id' => auth()->user()->rest_area_id,
                'tenant_id' => auth()->user()->tenant_id,
                'saldo' => 0,
                'created_at' => Carbon::now(),
            ]);
        }

        return response()->json(new ListInvoiceResource($data));
    } 
    
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = TransSaldo::with('trans_invoice')->ByTenant()->first();
            
            $invoice = new TransInvoice();
            $invoice->invoice_id = 'INV-'.Str::uuid();
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
        $data->status = TransInvoice::PAID;
        $data->pay_station_id = $request->pay_station_id ?? auth()->user()->id;
        $data->paid_date = Carbon::now();
        $data->save();

        return response()->json($data);
    }

    public function show()
    {
        $data = TransInvoice::findOrfail(request()->id);
        
        return response()->json(new InvoiceResource($data));
    }

}
