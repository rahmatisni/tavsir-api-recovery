<?php

namespace App\Http\Controllers\API\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Master\SatuanServices;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function __construct(protected SatuanServices $service)
    {
    }
    
    public function index()
    {
       return $this->service->list(request()->type);
    }

    public function indexTipe()
    {
       return $this->service->listTipe();
    }
}
