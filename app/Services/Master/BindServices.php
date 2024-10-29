<?php

namespace App\Services\Master;

use App\Exceptions\ApiRequestException;
use App\Models\Bind;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\PgJmtoSnap;
use App\Models\Satuan;
use Exception;

class BindServices
{
    public function list($customer_id)
    {
        return Bind::when($customer_id, function ($q) use ($customer_id) {
            $q->where('customer_id', $customer_id);
        })->get();
    }

    public function binding(array $param)
    {
        $payment_method = PaymentMethod::find($param['payment_method_id']);
        if ($payment_method->is_snap) {
            return $this->bindSnap($param);
        } else {
            return $this->bindStandar($param);
        }
    }

    public function unBinding($id)
    {
        $bind = Bind::findOrFail($id);
        if ($bind->is_snap) {
            return $this->unbindSnap($bind);
        } else {
            return $this->unBindStandar($bind);
        }
    }

    public function bindStandar($param)
    {
        $res = PgJmto::bindDD($param);
        if ($res->successful()) {
            $res = $res->json();
            if ($res['status'] == 'ERROR') {
                throw new Exception( json_encode($res), 400);
            }
            $bind = new Bind();
            $res = $res['responseData'];
            $res['customer_id'] = $param['customer_id'];
            $res['exp_date'] = $param['exp_date'];
            $res['payment_method_id'] = $param['payment_method_id'];
            $bind->fill($res);
            $bind->save();
            return [
                'data' => $bind,
                'info' => $res
            ];
        }
    }

    public function bindSnap($param)
    {
        $payload = [
            'bankCardNo' => $param['card_no'],
            'bankCardType' => 'D',
            "email"=> $param['email'],
            'expiryDate' => $param['exp_date'],
            'identificationNo' => '123456789',
            'identificationType' => '02',
            'accountName' => $param['customer_name'],
            'phoneNo' => $param['phone'],
            'deviceId'=> '12345679237',
            'channel' => 'mobilephone',
            'sofCode' => $param['sof_code'],
        ];
        $res = PgJmtoSnap::bindDD($payload);
        if ($res->successful()) {
            $res = $res->json();
            if ($res['responseCode'] != '2000100') {
                throw new Exception( json_encode($res), 400);
            }

            $bind = new Bind();
            $additionalInfo = $res['additionalInfo'] ?? [];
            $data = [
                'customer_id' => $param['customer_id'],
                'customer_name' => $param['customer_name'],
                'sof_code' => $additionalInfo['sofCode'],
                'card_no' => $additionalInfo['bankCardNo'],
                'phone' => $additionalInfo['phoneNo'],
                'email' => $additionalInfo['email'],
                'refnum' => $res['referenceNo'],
                'exp_date' => $param['exp_date'],
                'payment_method_id' => $param['payment_method_id'],
            ];
            $bind->fill($data);
            $bind->save();

            return [
                'data' => $bind,
                'info' => $res
            ];
        }

        throw new ApiRequestException($res->json());
    }

    public function rebinding($id)
    {
        $bind = Bind::whereNull('bind_id')->where('id', $id)->first();
        if (!$bind) {
            throw new Exception('Not Found.', 404);
        }

        if ($bind->payment_method?->is_snap) {
            return $this->rebindSnap($bind);
        } else {
            return $this->rebindStandar($bind);
        }
    }

    public function rebindStandar($bind)
    {
        $data = $bind->toArray();
        $res = PgJmto::bindDD($data);
        if ($res->successful()) {
            $res = $res->json();
            if ($res['status'] == 'ERROR') {
                throw new ApiRequestException($res);
            }
            $res = $res['responseData'];
            $res['customer_id'] = $data['customer_id'];
            $bind->fill($res);
            $bind->save();
            return [
                'data' => $bind,
                'info' => $res
            ];
        }
    }

    public function rebindSnap($bind)
    {
        $payload = [
            'bankCardNo' => $bind['card_no'],
            'bankCardType' => 'D',
            "email"=> $bind['email'],
            'expiryDate' => $bind['exp_date'],
            'identificationNo' => '12345',
            'identificationType' => '02',
            'accountName' => $bind['customer_name'],
            'phoneNo' => $bind['phone'],
            'deviceId'=> '12345679237',
            'channel' => 'mobilephone',
            'sofCode' => $bind['sof_code'],
        ];

        $res = PgJmtoSnap::bindDD($payload);
        if ($res->successful()) {
            $res = $res->json();
            if (substr($res['responseCode'], 0, 3) != '200') {
                throw new ApiRequestException($res);
            }

            $additionalInfo = $res['additionalInfo'] ?? [];
            $data = [
                'customer_id' => $bind['customer_id'],
                'customer_name' => $bind['customer_name'],
                'sof_code' => $additionalInfo['sofCode'],
                'card_no' => $additionalInfo['bankCardNo'],
                'phone' => $additionalInfo['phoneNo'],
                'email' => $additionalInfo['email'],
                'refnum' => $res['referenceNo'],
                'exp_date' => $bind['exp_date'],
            ];
            $bind->fill($data);
            $bind->save();

            return [
                'data' => $bind,
                'info' => $res
            ];
        }
    }

    public function bindValidate($id, $param)
    {
        $bind = Bind::whereNull('bind_id')->where('id', $id)->with('payment_method')->first();
        if (!$bind) {
            throw new Exception("Not Found", 400);
        }

        if($bind->is_snap){
            return $this->bindValidateSnap($bind, $param['otp']);
        }else{
            return $this->bindValidateStandar($bind, $param['otp']);
        }

    }

    public function bindValidateStandar($bind, $otp)
    {
        if ($bind->sof_code == 'MANDIRI') {
            $res = PgJmto::cardList(['sof_id' => '']);
            if ($res['status'] == 'ERROR') {
                throw new ApiRequestException($res);
            }

            $respon = collect($res['responseData']);
            $respon = $respon->where('sof_code','MANDIRI')->where('card_number',$bind->card_no)->first();
            if (!$respon) {
                throw new Exception('Unvalidate', 400);
            }

            $bind->bind_id = $respon['id'];
            $bind->save();
            return $bind;
        }

        $payload = $bind->toArray();
        $payload['otp'] = $otp;
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
                throw new ApiRequestException($res);
            }
            $respon = $res['responseData'];
            $bind->bind_id = $respon['bind_id'];
            $bind->save();
            return $bind;
        }
        throw new ApiRequestException($res);
    }

    public function bindValidateSnap($bind, $otp)
    {
        $payload = [
            'originalReferenceNo'=> $bind->refnum,
            'type'=> $bind->type ?? 'card',
            'otp'=> $otp,
            'deviceId'=> '12345679237',
            'channel'=> 'mobilephone',
            'sofCode'=> $bind->sof_code,
        ];

        $res = PgJmtoSnap::bindValidateDD($payload);
        if ($res->successful()) {
            $res = $res->json();
            if (substr($res['responseCode'], 0, 3) == '200') {
                $bind->bind_id = $res['additionalInfo']['bindId'];
                $bind->token = $res['bankCardToken'];
                $bind->save();
                return $bind;
            }
        }

        throw new ApiRequestException($res->json());
    }

    public function unBindStandar($bind)
    {
        if(!$bind->bind_id){
            $bind->delete();
            return ['message' => 'Success delete.'];
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
                return ['message' => 'Kartu sudah terunbind sebelumnya.'];
            }
            $bind->delete();
            return ['message' => 'Success unbind.'];
        }
        throw new ApiRequestException($res->json());
    }

    public function unbindSnap($bind)
    {
        if(!$bind->bind_id){
            $bind->delete();
            return ['message' => 'Success delete.'];
        }
        $payload = [
            'partnerReferenceNo' => $bind->refnum,
            'token' => $bind->token,
            'bindId' => (string) $bind->bind_id,
            'sofCode' => $bind->sof_code,
        ];
        $res = PgJmtoSnap::unBindDD($payload);
        if ($res->successful()) {
            $res = $res->json();
            if ($res['responseCode'] != '2000500') {
                return ['message' => 'Kartu sudah terunbind sebelumnya.'];
            }
            $bind->delete();
            return ['message' => 'Success unbind.'];
        }
        throw new ApiRequestException($res->json());
    }
}
