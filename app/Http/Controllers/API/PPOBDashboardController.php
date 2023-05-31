<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\TransactionDashboardResource;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use App\Services\External\KiosBankService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PPOBDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allProduct()
    {
        $data = ProductKiosBank::get();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saldoKiosbank(Request $request)
    {
        $kios = new KiosBankService();
        $data = $kios->cekDeposit();
        return response()->json($data);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transaction(Request $request)
    {
        $data = TransOrder::fromTravoy()->with('log_kiosbank','payment')
            ->when(($status = $request->status) && ($request->case == null), function($q) use ($status){
                $q->where('status', $status);
            })->when($case = $request->case, function($q) use ($case){
                switch ($case) {
                    case 'PENDING':
                        $q->whereIn('status',['PAYMENT_SUCCESS','READY']);
                        break;
                    
                    case 'WAITING':
                        $q->whereIn('status', ['WAITING_PAYMENT']);
                        break;
                    
                    case 'SUCCESS':
                        $q->whereIn('status',['DONE']);
                        break;
                    
                    default:
                        break;
                }
            })
            ->orderBy('created_at','desc')
            ->get();
        return response()->json(TransactionDashboardResource::collection($data));
    }

    public function penjualan(Request $request)
    {
        $data =  TransOrder::fromTravoy()->whereIn('status',[TransOrder::PAYMENT_SUCCESS,TransOrder::DONE])->get();
        $product = ProductKiosBank::orderBy('name')
        ->get();
        $record = [];
        foreach ($product as $value) {
            $count = $data->filter(function($item)use($value){
                return $item->codeProductKiosbank() == $value->kode;
            })->count();
            $record['label'][] = $value->name;
            $record['data'][] = $count;
        }
        return response()->json($record);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function pendapatan(Request $request)
    {
        $case =  $request->case ?? 'kotor';

        $data = [];
        $record = [];
        $year = Carbon::now()->format('Y');
        switch ($case) {
            case 'kotor':
                $data =  TransOrder::fromTravoy()->whereIn('status',[TransOrder::PAYMENT_SUCCESS,TransOrder::READY,TransOrder::DONE])
                ->whereYear('created_at', $year)
                ->select(
                        DB::raw("(DATE_FORMAT(created_at, '%m-%Y')) as created_at"),
                        DB::raw('sum(sub_total) as total'),
                    )
                ->orderBy('created_at')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, 'd-%m-%Y')"))
                ->pluck('total','created_at')->toArray();
                break;

            case 'bersih':
                $data =  TransOrder::fromTravoy()->whereIn('status',[TransOrder::PAYMENT_SUCCESS,TransOrder::READY,TransOrder::DONE])
                ->whereYear('created_at', $year)
                ->select(
                        DB::raw("(DATE_FORMAT(created_at, '%m-%Y')) as created_at"),
                        DB::raw('sum(sub_total) as total'),
                    )
                ->orderBy('created_at')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, 'd-%m-%Y')"))
                ->pluck('total','created_at')->toArray();
                break;
            
            default:
                # code...
                break;
        }
       foreach ($data as $key => $value) {
            $record['label'][] = $key;
            $record['data'][] = $value;
       }

       return response()->json($record);
    }
}
