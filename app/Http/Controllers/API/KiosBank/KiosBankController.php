<?php

namespace App\Http\Controllers\API\KiosBank;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderPulsaRequest;
use App\Http\Requests\UangElektronikRequest;
use App\Services\External\KiosBankService;
use Illuminate\Http\Request;

class KiosBankController extends Controller
{
    public function __construct(
        protected KiosBankService $service,
    )
    {}
   
    public function index(Request $request)
    {
        $kategori_pulsa = codefikasiNomor($request->nomor_hp);
        if($request->nomor_hp && !$kategori_pulsa){
            return response()->json(['message' => 'Nomor Salah'], 422);
        }

        $data = $this->service->getProduct($kategori_pulsa);
        return response()->json($data);
    }

    public function show($id)
    {
        $data = $this->service->showProduct($id);
        return response()->json($data);
    }

    public function listOperatorPulsa()
    {
        $data = $this->service->getListOperatorPulsa();
        return response()->json($data);
    }

    public function listProductOperatorPulsa($id)
    {
        $data = $this->service->listProductOperatorPulsa($id);
        return response()->json($data);
    }

    public function orderPulsa(OrderPulsaRequest $reqest)
    {
        $kategori_pulsa = codefikasiNomor($reqest->phone);
        if($reqest->phone && !$kategori_pulsa){
            return response()->json(['message' => 'Nomor Tidak Sesuai Dengan Produk Yang Dipilih!'], 422);
        }
        $datax = $this->service->getProduct($kategori_pulsa);
        $validator = json_decode($datax['PULSA']->kode);
        dd($validator);
        $data = $this->service->orderPulsa($reqest->validated(), $validator);
        return response()->json($data);
    }

    public function callback(Request $reqest)
    {
        $data = $this->service->callback($reqest->all());
        return response()->json($data);
    }

    public function cekDeposit()
    {
        $data = $this->service->cekDeposit();
        return response()->json($data);
    }

    public function orderUangElektronik(UangElektronikRequest $request)
    {
        $data = $this->service->uangelEktronik($request->validated());
        return response()->json($data);
    }

    
}
