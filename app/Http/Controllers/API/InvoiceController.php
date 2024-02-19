<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ListInvoiceResource;
use App\Models\TransInvoice;
use App\Models\TransInvoiceDerek;
use App\Models\TransOrder;
use App\Models\TransSaldo;
use App\Models\User;
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
                $query->where('trans_invoice.status', '=',  request('status'));
            }
            if (request('filter') != '') {
                $query->select('trans_invoice.*');
                $query->addSelect('users.name');
                $query->join('users', 'trans_invoice.cashier_id', '=', 'users.id');
                $filter = request('filter');
                $query->where('invoice_id', 'like', "%" . $filter . "%");
                $query->orWhere('claim_date', 'like', "%" . $filter . "%");
                $query->orWhere('paid_date', 'like', "%" . $filter . "%");
                $query->orWhere('nominal', 'like', "%" . $filter . "%");
                $query->orWhere('trans_invoice.status', 'like', "%" . $filter . "%");
                $query->orWhere('name', 'like', "%" . $filter . "%");
            }

            if (request('sort')) {
                $sort = explode('&', request('sort'));
                $query->orderBy($sort[0], $sort[1]);
            } else {
                $query->orderBy('claim_date', 'desc');
            }
        }])->ByRole()
            ->when($rest_area_id = request()->rest_area_id, function ($query) use ($rest_area_id) {
                return $query->where('rest_area_id', $rest_area_id);
            })
            ->when($tenant_id = request()->tenant_id, function ($query) use ($tenant_id) {
                return $query->where('tenant_id', $tenant_id);
            })->get();
        // dd( DB::getQueryLog());             

        return response()->json(ListInvoiceResource::collection($data));
    }

    public function indexDerek()
    {
        DB::enableQueryLog();

        $data = TransInvoiceDerek::get();
        return response()->json($data);
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
            $invoice->invoice_id = ($data->rest_area_id ?? '0').'-'.($data->tenant_id ?? '0').'-INV-' . date('YmdHis');
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
    public function storeDerek(Request $request)
    {
        try {
            DB::beginTransaction();
            $uuid = Str::uuid();
            $now = Carbon::now()->format('Y-m-d H:i:s');

            $hasil = [];
            $nominal = 0;
            $trans_order = ($request->trans_order_id);
            foreach ($trans_order as $value) {
                $data = TransOrder::byRole()
                ->where('order_type', '=', TransOrder::ORDER_DEREK_ONLINE)
                ->whereIn('status', [TransOrder::PAYMENT_SUCCESS, TransOrder::DONE])->whereNull('invoice_id')->find($value);
                if($data){
                    $hasil[]= $value;
                    $data->invoice_id = $uuid;
                    $nominal = $nominal + $data->sub_total;
                    $data->save();
                }
      
            }

            if($nominal > 0){

                $invoice = new TransInvoiceDerek();
                $invoice->id = 'DRK'.'-'.$uuid;
                $invoice->invoice_id = $uuid;
                $invoice->cashier_id = auth()->user()->id;
                $invoice->nominal = $nominal ;
                $invoice->claim_date = $now;
                $invoice->status = TransInvoice::UNPAID;
                $invoice->save();
            }

           
            DB::commit();

            return response()->json(['status' => 'Berhasil', 'trans_order_id' => $hasil]);
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
        $data->pay_station_id = $request->pay_station_id ?? auth()->user()->paystation_id;
        $data->pay_petugas_id = auth()->user()->id;
        $data->paid_date = Carbon::now();
        $data->kwitansi_id = ($data->trans_saldo?->rest_area_id ?? '0').'-'.($data->trans_saldo?->tenant_id ?? '0').'-'.($data->pay_petugas_id ?? '0').'-RCP-'. date('YmdHis'); ;
        $data->save();

        return response()->json($data);
    }

    public function paidInvoiceDerek(Request $request, $id)
    {
        $data = TransInvoiceDerek::findOrfail($id);
        if ($data->status == TransInvoiceDerek::PAID) {
            return response()->json(['message' => 'Invoice sudah dibayar'], 400);
        }

        $data->status = TransInvoice::PAID;
        $data->pay_petugas_id = auth()->user()->id;
        $data->paid_date = Carbon::now();
        $data->kwitansi_id = ($data->pay_petugas_id ?? '0').'-RCP-'. date('YmdHis'); ;
        $data->save();

        return response()->json($data);
    }

    public function show()
    {
        $data = TransInvoice::findOrfail(request()->id);

        return response()->json(new InvoiceResource($data));
    }
}
