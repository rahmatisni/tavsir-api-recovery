<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PgJmto;

class PaymentGatewayController extends Controller
{
    public function sofList()
    {
        $res = PgJmto::service('POST','/sof/list', ['sof_id' => request()->sof_id]);
        return $res->json();
    }

    public function vaCreate()
    {
        $res = PgJmto::service('POST','/va/create', request()->all());
        return $res->json();
    }

    public function vaStatus()
    {
        $res = PgJmto::service('POST','/va/cekstatus', request()->all());
        return $res->json();
    }

    public function vaDelete()
    {
        $res = PgJmto::service('POST','/va/delete', request()->all());
        return $res->json();
    }

    public function ddInquiry()
    {
        $res = PgJmto::service('POST','/directdebit/inquiry', request()->all());
        return $res->json();
    }

    public function ddPayment()
    {
        $res = PgJmto::service('POST','/directdebit/payment', request()->all());
        return $res->json();
    }
}
