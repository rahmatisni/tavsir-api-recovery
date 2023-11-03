<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\PgJmto;
use App\Models\User;


class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paymentMethods = PaymentMethod::all();
        if (auth()->user()->role == User::TENANT) {
            $intersectbucket = json_decode(Tenant::find(auth()->user()->tenant_id)->list_payment_bucket ?? '[]');
            $intersect = json_decode(Tenant::find(auth()->user()->tenant_id)->list_payment ?? '[]');
        }
        else {
            $intersectbucket = json_decode(Tenant::find($request->tenant_id)->list_payment_bucket ?? '[]');
            $intersect = json_decode(Tenant::find($request->tenant_id)->list_payment ?? '[]');
        }
        foreach ($paymentMethods as $value) {
            if (in_array($value->id, $intersectbucket)) {
                $value->is_listed = true;
            } else {
                $value->is_listed = false;
            }
            if (in_array($value->id, $intersect)) {
                $value->is_active = true;
            } else {
                $value->is_active = false;
            }
            
        }

        return response()->json($paymentMethods);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\PaymentMethodRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PaymentMethodRequest $request)
    {
        $paymentMethod = PaymentMethod::create($request->all());
        return response()->json($paymentMethod);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaymentMethodRequest  $request
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($request->all());
        return response()->json($paymentMethod);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return response()->json($paymentMethod);
    }
}
