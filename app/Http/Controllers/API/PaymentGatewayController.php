<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PgJmto;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function sofList()
    {
        $res = PgJmto::service('/sof/list', request()->all());
        return $res->json();
    }

    public function vaCreate()
    {
        $res = PgJmto::service('/va/create', request()->all());
        return $res->json();
    }

    public function vaStatus()
    {
        $res = PgJmto::service('/va/cekstatus', request()->all());
        return $res->json();
    }

    public function vaDelete()
    {
        $res = PgJmto::service('/va/delete', request()->all());
        return $res->json();
    }
}
