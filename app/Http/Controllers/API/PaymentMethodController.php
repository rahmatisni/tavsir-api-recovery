<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Http\Requests\PaymentMethodRuleRequest;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRule;
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

    public function indexPublic(Request $request)
    {
        $paymentMethods = PaymentMethod::where('id', $request->id)->get();
        return response()->json($paymentMethods);
    }


    public function indexSof(Request $request)
    {
        $paymentMethodsparent = PaymentMethod::whereNotNull('integrator')->distinct()->pluck('integrator');

        $data = [];
        foreach ($paymentMethodsparent as $v){
            $datas = [];
            $datas['integrator'] = $v;
            $datas['data'] = PaymentMethod::where('integrator', $v)->get();
            $data[] =$datas; 
        }
        return response()->json($data);
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
    public function show($id)
    {
        $paymentMethod = PaymentMethod::with('payment_method_rule')->findOrfail($id);
        return response()->json($paymentMethod);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaymentMethodRequest  $request
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(PaymentMethodRequest $request, $id)
    {
        $paymentMethod = PaymentMethod::findOrfail($id);
        $paymentMethod->update($request->all());
        return response()->json($paymentMethod);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::findOrfail($id);
        $paymentMethod->payment_method_rule()->delete();
        $paymentMethod->delete();
        return response()->json($paymentMethod);
    }

    public function storeRule(PaymentMethodRuleRequest $request)
    {
        $data = PaymentMethodRule::create($request->validated());
        return response()->json($data);
    }

    public function updateRule(PaymentMethodRuleRequest $request, $id)
    {
        $data = PaymentMethodRule::findOrFail($id);
        $data->fill($request->validated());
        $data->save();
        return response()->json($data);
    }

    public function deleteRule($id)
    {
        $data = PaymentMethodRule::findOrFail($id);
        $data->delete();
        return response()->json($data);
    }

}
