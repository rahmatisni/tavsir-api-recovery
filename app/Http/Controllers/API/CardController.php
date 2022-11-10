<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BindRequest;
use App\Http\Requests\BindValidateRequest;
use App\Models\Bind;
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
                $res = $res->json();
                if ($res['status'] == 'ERROR') {
                    return response()->json($res, 400);
                }
                $bind = new Bind();
                $res = $res['responseData'];
                $res['customer_id'] = $request['customer_id'];
                $bind->fill($res);
                $bind->save();
                return $bind;
            }
            return response()->json($res->json(), 400);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 400);
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
            $res = PgJmto::bindValidateDD($payload);

            if ($res->successful()) {
                $res = $res->json();
                if ($res['status'] == 'ERROR') {
                    return response()->json($res, 400);
                }
                $respon = $res['responseData'];
                $bind->bind_id = $respon['bind_id'];
                $bind->save();
                return $bind;
            }
            return response()->json($res->json(), 400);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 400);
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
                $res = $res->json();
                if ($res['status'] == 'ERROR') {
                    return response()->json($res, 400);
                }
                $bind->delete();
                return ['message' => 'Success unbind.'];
            }
            return response()->json($res->json(), 400);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 400);
        }
    }
}
