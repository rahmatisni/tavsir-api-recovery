<?php

namespace App\Http\Controllers\API\V2\Pos;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pos\TransStockKartuResource;
use App\Http\Resources\StockMasukResource;
use App\Models\User;
use App\Services\Pos\StockServices;
use Illuminate\Http\Request;

class TransStockV2Controller extends Controller
{
    public function __construct(protected StockServices $service)
    {
        $this->middleware('role:'.User::TENANT.','.User::CASHIER)->except(['index','show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function kartu(Request $request)
    {
        $data = $this->service->kartuStock($request->search, $request->filter);
        return $this->responsePaginate(TransStockKartuResource::class, $data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function masuk(Request $request)
    {
        $data = $this->service->stockMasuk($request->search, $request->filter);
        return $this->responsePaginate(StockMasukResource::class, $data);
    }
}
