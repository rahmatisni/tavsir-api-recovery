<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BindRequest;
use App\Http\Requests\BindValidateRequest;
use App\Models\Voucher;
use App\Http\Requests\VoucherRequest;
use App\Models\Bind;
use App\Models\Dto\BindingDto;
use App\Models\PgJmto;

class CardController extends Controller
{
    public function index()
    {
        $data = Bind::when($customer_id = request()->customer_id, function ($q) use ($customer_id) {
            $q->where('customer_id', $customer_id);
        })->get();
        return response()->json($data);
    }

    public function bind(BindRequest $request)
    {
        try {
            $res = PgJmto::bindDD($request->validated());
            if ($res->successful()) {
                $respon = $res->json();
                $bind = new Bind();
                $respon = $respon['responseData'];
                $respon['customer_id'] = $request['customer_id'];
                $bind->fill($respon);
                $bind->save();
                return $bind;
            }
            dd($res);
            return response()->json($res);
        } catch (\Throwable $th) {
            return response()->json($th);
        }
    }

    public function bindValidate(BindValidateRequest $request, $id)
    {
        try {
            $bind = Bind::whereNull('bind_id')->where('id', $id)->first();
            if (!$bind) {
                return response()->json(['message' => 'Not Found.'], 404);
            }
            $payload = $bind->toArray();
            $payload['otp'] = $request->otp;
            unset($payload['created_at']);
            unset($payload['updated_at']);
            unset($payload['id']);
            unset($payload['customer_id']);
            unset($payload['is_valid']);
            unset($payload['bind_id']);
            unset($payload['refnum']);
            $res = PgJmto::bindValidateDD($payload);

            if ($res->successful()) {
                $respon = $res['responseData'];
                $bind->bind_id = $respon['bind_id'];
                $bind->save();
                return $bind;
            }
            return response()->json($res);
        } catch (\Throwable $th) {
            return response()->json($th);
        }
    }

    public function unBind($id)
    {
        try {
            $bind = Bind::whereNotNull('bind_id')->where('id', $id)->first();
            if (!$bind) {
                return response()->json(['message' => 'Not Found.'], 404);
            }
            $payload = $bind->toArray();
            unset($payload['created_at']);
            unset($payload['updated_at']);
            unset($payload['id']);
            unset($payload['customer_id']);
            unset($payload['is_valid']);
            unset($payload['bind_id']);
            $res = PgJmto::unBindDD($payload);
            if ($res->successful()) {
                $bind->delete();
                return ['message' => 'Success unbind.'];
            }
            return response()->json($res);
        } catch (\Throwable $th) {
            return response()->json($th);
        }
    }
}
