<?php

namespace App\Http\Controllers\API\KiosBank;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderPulsaRequest;
use App\Http\Requests\UangElektronikRequest;
use App\Services\External\KiosBankService;
use Facade\FlareClient\Glows\Recorder;
use Illuminate\Http\Request;
use App\Models\KiosBank\ProductKiosBank;

class KiosBankController extends Controller
{
    public function __construct(
        protected KiosBankService $service,
    ) {
    }

    public function index(Request $request)
    {
        $kategori_pulsa = codefikasiNomor($request->nomor_hp);
        $kategori = ($request?->kategori);
        $sub_kategori = ($request?->sub_kategori);

        if ($request->nomor_hp && !$kategori_pulsa) {
            return response()->json(['message' => 'Nomor Salah'], 422);
        }

        $data = $this->service->getProduct($kategori_pulsa, $kategori, $sub_kategori);
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
        if ($data['rc'] != '00') {
            $data['record'] = [];
        }

        $data_final=[];
        $harga = $data['record'];
        // $product = ProductKiosBank::get();
        foreach ($harga as $key => $val) {
            // $harga_jual = $product->where('kode', $val['code'])
            //     ->only([
            //         'kode',
            //         'harga'
            //     ])
            //     ->first();
            $harga_jual = ProductKiosBank::where([['kode', $val['code']]])
            ->select([
                    'kode',
                    'harga','is_active'
                ])
                ->firstOrFail();

            if ($harga_jual['is_active'] == '0')
            {
                unset($data['record'][$key]);
            }
            else {
                $data['record'][$key]['price_jmto'] = $data['record'][$key]['price'] + $harga_jual['harga'];
                array_push($data_final,$data['record'][$key]);
            }

        }

        if  ($data['rc'] == '00') {
            $data = [
                'record' => $data_final,
                "rc" => "00"
            ];
        }
        else {
            return response()->json(['message' => 'Produk Sedang Gangguan', 'errors' => 'Produk Sedang Gangguan'], 422);
        }
      

        return response()->json($data);
    }

    public function orderPulsa(OrderPulsaRequest $reqest)
    {
        $kategori_pulsa = codefikasiNomor($reqest->phone);
        if ($reqest->phone && !$kategori_pulsa) {
            return response()->json(['message' => 'Nomor Tidak Sesuai Dengan Produk!', 'errors' => 'Nomor Tidak Sesuai Dengan Produk!'], 422);
        }

        $datax = $this->service->getProduct($kategori_pulsa);
        $validatorpulsa = [];
        $validatordata = [];

        if (isset($datax['Pulsa'])) {
            $validatorpulsa = json_decode($datax['Pulsa']);
        }

        if (isset($datax['Paket Data'])) {
            $validatordata = json_decode($datax['Paket Data']);
        }

        $validatorarr = array();
        foreach ($validatorpulsa as $v) {
            array_push($validatorarr, $v->kode);
        }
        foreach ($validatordata as $y) {
            array_push($validatorarr, $y->kode);
        }
        if (!in_array($reqest->code, $validatorarr)) {
            return response()->json(['message' => 'Nomor Tidak Sesuai Dengan Produk!', 'errors' => 'Nomor Tidak Sesuai Dengan Produk!'], 422);
        }

        $product_jmto = ProductKiosBank::where('kode', $reqest->code)->first();
        $product_kios = $this->service->listProductOperatorPulsa($product_jmto->prefix_id);
        if ($product_kios['rc'] != '00') {
            return response()->json(['message' => 'Product Tidak ditemukan!', 'errors' => 'Product Tidak ditemukan!'], 422);
        }
        if (!isset($product_kios['record'][0]['price'])) {
            return response()->json(['message' => 'Product maintenance', 'errors' => 'Product maintenance'], 422);
        }
        $product_kios = collect($product_kios['record'])->firstWhere('code', $reqest->code);
        if (!$product_kios) {
            return response()->json(['message' => 'Product Tidak ditemukan!', 'errors' => 'Product Tidak ditemukan!'], 422);
        }

        $harga_kios = $product_kios['price'];
        $harga_final = $harga_kios + ($product_jmto->harga ?? 0);

        $data = $this->service->orderPulsa($reqest->validated(), $harga_kios, $harga_final);
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
        $mandiri = ['6032', '5893', '6221'];
        if ((substr($request['code'], 0, 3) == '753') && ((strlen($request['phone']) != 16) || (!in_array(substr($request['phone'], 0, 4), $mandiri)))) {
            // return response()->json([
            //     'status' => 'error',
            //     'message' => 'Nomor Kartu Anda Tidak Valid'
            // ], 422);

            return response()->json(['message' => 'Nomor Kartu Anda Tidak Valid', 'errors' => 'Nomor Kartu Anda Tidak Valid'], 422);

        }

        $data = $this->service->uangelEktronik($request->validated());
        if ($data instanceof \Exception) {
            return response()->json(['message' => 'Silahkan Coba Kembali', 'errors' => 'Silahkan Coba Kembali'], 500);
        }

        if ($data['rc']) {
            return response()->json(['message' => $data['description'], 'errors' => $data['description']], 422);
        }



        return response()->json($data);
    }

    public function getSubKategoriProduct()
    {
        $data = $this->service->getSubKategoriProduct();
        return response()->json($data);
    }

}