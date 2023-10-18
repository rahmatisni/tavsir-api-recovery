<?php

namespace App\Http\Controllers\API\V2\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransStockStoreRequest;
use App\Http\Resources\Pos\TransStockDetilResource;
use App\Http\Resources\Pos\TransStockKartuResource;
use App\Http\Resources\Pos\TransStockResource;
use App\Http\Resources\Pos\DropDownResource;

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

    public function showKartu($id)
    {
        $data = $this->service->showKartu($id);
        return $this->response(new TransStockKartuResource($data));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function masuk(Request $request)
    {
        $data = $this->service->stockMasuk($request->search, $request->filter);
        return $this->responsePaginate(TransStockResource::class, $data);
    }

    public function showMasukKeluar($id)
    {
        $data = $this->service->showMasukKeluar($id);
        return $this->response(new TransStockResource($data));
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function keluar(Request $request)
    {
        $data = $this->service->stockKeluar($request->search, $request->filter);
        return $this->responsePaginate(TransStockResource::class, $data);
    }

    public function storeMasuk(TransStockStoreRequest $request)
    {
        $data = $this->service->storeMasuk($request->validated());
        return $this->response($data);
    }

    public function storeKeluar(TransStockStoreRequest $request)
    {
        $data = $this->service->storeKeluar($request->validated());
        return $this->response($data);
    }

    public function changeStatus($id)
    {
        $data = $this->service->changeStatus($id);
        return $this->response($data);
    }
    public function listproduk()
    {
        $data = $this->service->listProduk();
        // return $data;
        return ['data' => response()->json(DropDownResource::collection($data))];
    }
    public function listprodukRAW()
    {
        $data = $this->service->listProdukRAW();
        // return $data;
        return ['data' => response()->json(DropDownResource::collection($data))];

    }
}
