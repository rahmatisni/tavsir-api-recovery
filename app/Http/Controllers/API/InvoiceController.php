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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        DB::enableQueryLog();

        $data = TransSaldo::with(['trans_invoice' => function ($query) {
            if (request('status') != '') {
                $query->where('status', '=',  request('status'));
            }
            if (request('filter') != '') {
                $filter = request('filter');
                $query->where('invoice_id', 'like', "%" . $filter . "%");
                $query->orWhere('claim_date', 'like', "%" . $filter . "%");
                $query->orWhere('paid_date', 'like', "%" . $filter . "%");
                $query->orWhere('nominal', 'like', "%" . $filter . "%");
                $query->orWhere('status', 'like', "%" . $filter . "%");
            }
        }])->ByRole()
            ->when($rest_area_id = request()->rest_area_id, function ($query) use ($rest_area_id) {
                return $query->where('rest_area_id', $rest_area_id);
            })
            ->when($tenant_id = request()->tenant_id, function ($query) use ($tenant_id) {
                return $query->where('tenant_id', $tenant_id);
            })->get();
        return response()->json(ListInvoiceResource::collection($data));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = TransSaldo::with('trans_invoice')->ByTenant()->first();
            if (!$data) {
                return response()->json(['message' => 'Saldo tidak ditemukan'], 404);
            }

            if ($data->saldo < $request->nominal) {
                return response()->json(['message' => 'Saldo tidak mencukupi'], 400);
            }

            $invoice = new TransInvoice();
            $invoice->invoice_id = 'INV-' . strtolower(Str::random(16));
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

            return response()->json($th->getMessage(), 500);
        }
    }

    public function paid(Request $request, $id)
    {
        $data = TransInvoice::findOrfail($id);
        if ($data->status == TransInvoice::PAID) {
            return response()->json(['message' => 'Invoice sudah dibayar'], 400);
        }
        $user = auth()->user();
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN salah'], 400);
        }

        $cashier = $data->cashier;
        if (!Hash::check($request->pin_cashier, $cashier->pin)) {
            return response()->json(['message' => 'PIN cashier salah'], 400);
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
