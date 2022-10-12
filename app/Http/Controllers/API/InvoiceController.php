<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
        $data = TransSaldo::with('trans_invoice')->byCashier()->first();
        if(!$data){
            $data = TransSaldo::create([
                'cashier_id' => auth()->user()->id,
                'rest_area_id' => auth()->user()->rest_area_id,
                'tenant_id' => auth()->user()->tenant_id,
                'saldo' => 0,
                'created_at' => Carbon::now(),
            ]);
        }

        return response()->json($data);
    } 
    
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = TransSaldo::with('trans_invoice')->byCashier()->first();
            
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
        $data->paid_date = Carbon::now();
        $data->save();

        return response()->json($data);
    }

}
