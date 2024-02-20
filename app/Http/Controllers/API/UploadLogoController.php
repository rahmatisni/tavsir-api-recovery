<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\NumberSaveRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Http\Resources\TravShop\NumberSaveResource;
use App\Models\NumberSave;
use App\Services\Master\UploadLogoServices;
use App\Services\Travshop\NumberSaveServices;
use Illuminate\Http\Request;

class UploadLogoController extends Controller
{
    public function __construct(protected UploadLogoServices $service)
    {        
    }

    public function index(Request $request)
    {
        $data = $this->service->listKategori();
        return $this->response($data);
    }

    public function store(UploadLogoRequest $request)
    {
        $data = $this->service->upload($request);
        return response()->json($data);
    }
}
