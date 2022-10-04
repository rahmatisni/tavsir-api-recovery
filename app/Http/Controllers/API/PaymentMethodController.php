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
                $fee = PgJmto::feeBriVa();
                if($fee){
                    $value->fee = $fee;
                    $value->save();
                }
            }

            if($value->code_name == 'pg_va_mandiri'){
                $fee = PgJmto::feeMandiriVa();
                if($fee){
                    $value->fee = $fee;
                    $value->save();
                }
                $value->save();
            }

            if($value->code_name == 'pg_va_bni'){
                $fee = PgJmto::feeBniVa();
                if($fee){
                    $value->fee = $fee;
                    $value->save();
                }
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
        switch ($$paymentMethod->code_name) {
            case 'pg_va_bri':
                $fee = PgJmto::feeBriVa();
                if($fee){
                    $paymentMethod->fee = $fee;
                    $paymentMethod->save();
                }
            break;
            
            case 'pg_va_mandiri':
                    $fee = PgJmto::feeMandiriVa();
                    if($fee){
                        $paymentMethod->fee = $fee;
                        $paymentMethod->save();
                    }
            break;

            case 'pg_va_bni':
                    $fee = PgJmto::feeBniVa();
                    if($fee){
                        $paymentMethod->fee = $fee;
                        $paymentMethod->save();
                    }
            break;
            
            default:
                # code...
            break;
        }

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
