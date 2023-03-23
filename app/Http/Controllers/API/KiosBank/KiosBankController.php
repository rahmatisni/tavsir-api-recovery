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
        if($data['rc'] != '00')
        {
            $data['record'] = [];
        }
        $harga = $data['record'];

        foreach ($harga as $v) {
            $data['record']['price'] = $v['price'] +1;
        }
        return response()->json($data);
    }

    public function orderPulsa(OrderPulsaRequest $reqest)
    {
        $kategori_pulsa = codefikasiNomor($reqest->phone);
        if($reqest->phone && !$kategori_pulsa){
            return response()->json(['message' => 'Nomor Tidak Sesuai Dengan Produk!', 'errors' => 'Nomor Tidak Sesuai Dengan Produk!'], 422);
        }

        $datax = $this->service->getProduct($kategori_pulsa);
        $validatorpulsa = json_decode($datax['PULSA']);
        $validatordata = json_decode($datax['Paket Data']);

        $validatorarr = array();
        foreach ($validatorpulsa as $v) {
            array_push($validatorarr, $v->kode);
        }
        foreach ($validatordata as $y) {
            array_push($validatorarr, $y->kode);
        }
        if (!in_array($reqest->code, $validatorarr))
        {
            return response()->json(['message' => 'Nomor Tidak Sesuai Dengan Produk!', 'errors' => 'Nomor Tidak Sesuai Dengan Produk!'], 422);
        }

        $data = $this->service->orderPulsa($reqest->validated());
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
