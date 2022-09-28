<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Models\PgJmto;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::all();
        foreach ($paymentMethods as $value) {
            if($value->code_name == 'pg_va_bri'){
                $value->fee = PgJmto::feeBriVa();
                $value->save();
            }

            if($value->code_name == 'pg_va_mandiri'){
                $value->fee = PgJmto::feeMandiriVa();
                $value->save();
            }

            if($value->code_name == 'pg_va_bni'){
                $value->fee = PgJmto::feeBniVa();
                $value->save();
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
        if($paymentMethod->code_name == 'pg_va_bri'){
            $paymentMethod->fee = PgJmto::feeBriVa();
        }

        if($paymentMethod->code_name == 'pg_va_mandiri'){
            $paymentMethod->fee = PgJmto::feeMandiriVa();
        }

        if($paymentMethod->code_name == 'pg_va_bni'){
            $paymentMethod->fee = PgJmto::feeBniVa();
        }
        $paymentMethod->save();

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
