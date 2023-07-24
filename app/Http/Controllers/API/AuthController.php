<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BukaTutupTenantRequest;
use App\Http\Requests\CloseCashierRequest;
use App\Http\Requests\PinRequest;
use App\Http\Requests\PinStoreRequest;
use App\Http\Resources\ProfileResource;
use App\Models\TransCashbox;
use App\Models\TransOperasional;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Http\Resources\TravShop\TsTenantResource;
use App\Models\Business;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $count = $user->accessTokens()->count();
                if ($request->check) {
                    return response()->json([
                        'is_login_other_device' => $count > 0 ? true : false,
                        'login_count' => $count
                    ], 200);
                }
                if ($user->role == User::TENANT) {
                    if($user->tenant->is_subscription == 0){
                        $response = ["message" => 'Tenant not subscription'];
                        return response($response, 422);
                    }
                }
                if ($user->role == User::CASHIER) { 
                    if($user->is_subscription == 0){
                        $response = ["message" => "Not Subscription"];
                        return response($response, 422);
                    }
                    if ($count > 0) {
                        if ($user->fcm_token) {
                            sendNotif($user->fcm_token, 'Login di perangkat lain', []);
                        }
                    }
                    $user->accessTokens()->delete();
                }

                //Cek subscription aktif
                $business_id = 0;
                if(in_array($user->role,[User::OWNER, User::TENANT, User::CASHIER])){
                    switch ($user->role) {
                        case User::OWNER:
                            $business_id = $user->business->id ?? 0;
                            break;
                        case User::TENANT:
                            $business_id = $user->tenant->business->id ?? 0;
                            break;
                        case User::CASHIER:
                            $business_id = $user->tenant->business->id ?? 0;
                            break;
                        
                        default:
                            # code...
                            break;
                    }
                    $subscription_end = Business::find($business_id)?->subscription_end;
                    if($subscription_end){
                        $subscription_end = Carbon::parse($subscription_end);
                        $is_active = $subscription_end->lt(Carbon::now()->subDay());
                        if($is_active){
                            $response = ["message" => "Subscription tidak aktif, terakhir subscription ".$subscription_end->format('d F Y').' / '.$subscription_end->diffForHumans()];
                            return response($response, 422);
                        }
                    }else{
                        $response = ["message" => "Invalid Subscription"];
                        return response($response, 422);
                    }
                }

                //End Cek

                $tokenResult = $user->createToken('Personal');
                $token = $tokenResult->accessToken;

                if ($request->fcm_token != '' && $request->fcm_token != null) {
                    User::where('id', $user->id)->update(['fcm_token' => $request->fcm_token]);
                }

                $response = [
                    'access_token' => $token,
                ];
                return response()->json($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" => 'User does not exist'];
            return response($response, 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->delete();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function profile()
    {
        return response()->json(new ProfileResource(auth()->user()));
    }

    public function resetPin()
    {
        $user = auth()->user();
        $user->reset_pin = User::WAITING_APPROVE;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Atur ulang PIN menunggu persetujuan'
        ]);
    }

    public function pinStore(PinStoreRequest $request)
    {
        $user = auth()->user();
        if ($user->pin != null && $user->reset_pin != User::APPROVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Atur ulang PIN belum ' . $user->reset_pin
            ], 422);
        }
        $user->pin = bcrypt($request->pin);
        $user->reset_pin = null;
        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Autur PIN successfully'
        ]);
    }
    public function checkOpenCashier()
    {
        $user = auth()->user();
        $cek = TransOperational::where('tenant_id', $user->tenant_id)->where('casheer_id', $user->id)
            ->whereNull('end_date')
            ->exists();

        return response()->json([
            'status' => 'success',
            'data' => $cek
        ]);
    }
    public function openCashier(PinRequest $request)
    {
        DB::beginTransaction();
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id ?? $user->supertenant_id);
        if (!$tenant) {
            return response()->json([
                'status' => 'error',
                'message' => 'User Tenant ID ' . $user->tenant_id . ' invalid'
            ], 400);
        }
        try {
            if (Hash::check($request->pin, $user->pin)) {
                $cek = TransOperational::where('casheer_id', $user->id)
                    ->where('tenant_id', $user->tenant_id)
                    ->whereNull('end_date')
                    ->get();

                if ($cek->count() > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Silahkan tutup kasir terlebih dahulu'
                    ], 422);
                }

                $count_periode = TransOperational::where('casheer_id', $user->id)
                    ->where('tenant_id', $user->tenant_id ?? $user->supertenant_id )
                    ->whereDate('start_date', '=', date('Y-m-d'))
                    ->latest()->first();
                if ($count_periode) {
                    $count_periode = $count_periode->periode + 1;
                } else {
                    $count_periode = 1;
                }

                $trans_op = new TransOperational();
                $trans_cashbox = new TransCashbox();
                $trans_cashbox->initial_cashbox = $request->cashbox;

                $trans_op->tenant_id = $user->tenant_id ?? $user->supertenant_id;
                $trans_op->casheer_id = $user->id;
                $trans_op->periode = $count_periode;
                $trans_op->start_date = Carbon::now();
                $trans_op->save();
                $trans_op->trans_cashbox()->save($trans_cashbox);

                // otomatis buka toko jika buka kasir
                if ($tenant->is_open == 0) {
                    $tenant->update(['is_open' => 1]);
                }
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Open cashier successfully'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'PIN verification failed'
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json($th->getMessage(), 500);
        }
    }

    public function cekPin(Request $request)
    {

        DB::beginTransaction();
        $user = auth()->user();
        // $tenant = Tenant::find($user->tenant_id ?? $user->supertenant_id);
        // if (!$tenant) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User Tenant ID ' . $user->tenant_id . ' invalid'
        //     ], 400);
        // }
        try {
            if (Hash::check($request->pin, $user->pin)) {
               return response()->json([
                'status' => 'success',
                'message' => 'PIN verification success'
            ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'PIN verification failed'
            ], 422);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json($th->getMessage(), 500);
        }
    }

    public function closeCashier(CloseCashierRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->pin, $user->pin)) {
            $data = TransOperational::where('casheer_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->whereNull('end_date')
                ->first();
            if (!$data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silahkan buka kasir terlebih dahulu'
                ], 422);
            }

            try {
                //code...

                DB::beginTransaction();

                $end_date = Carbon::now();
                $data->duration = $data->start_date->diffInSeconds($end_date);
                $data->end_date = $end_date;
                $data->save();
                $trans_cashbox = $data->trans_cashbox ? $data->trans_cashbox : new TransCashbox();
                $trans_cashbox->cashbox = $request->cashbox;
                $trans_cashbox->pengeluaran_cashbox = $request->pengeluaran_cashbox;
                $trans_cashbox->description = $request->description;

                $data_all = TransOrder::with('payment_method')->where('status', TransOrder::DONE)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('casheer_id', $user->id)
                    ->whereBetween('created_at', [$data->start_date, $data->end_date])
                    ->get();
                $total_order = $data_all;
                $total_order = $total_order->where('payment_method.code_name', 'cash');
                $total_order = $total_order->sum('sub_total') + $total_order->sum('addon_total');

                $trans_cashbox->rp_cash = $total_order;
                $trans_cashbox->different_cashbox = $request->cashbox - $total_order  - $request->pengeluaran_cashbox;
                $trans_cashbox->input_cashbox_date = Carbon::now();

                $rp_va_bri = $data_all;
                $rp_va_bri = $rp_va_bri->where('payment_method.code_name', 'pg_va_bri');
                $rp_va_bri = $rp_va_bri->sum('sub_total') + $rp_va_bri->sum('addon_total');
                $trans_cashbox->rp_dd_bri = $rp_va_bri;

                $rp_dd_bri = $data_all;
                $rp_dd_bri = $rp_dd_bri->where('payment_method.code_name', 'pg_dd_bri');
                $rp_dd_bri = $rp_dd_bri->sum('sub_total') + $rp_dd_bri->sum('addon_total');
                $trans_cashbox->rp_dd_bri = $rp_dd_bri;

                $rp_va_mandiri = $data_all;
                $rp_va_mandiri = $rp_va_mandiri->where('payment_method.code_name', 'pg_va_mandiri');
                $rp_va_mandiri = $rp_va_mandiri->sum('sub_total') + $rp_va_mandiri->sum('addon_total');
                $trans_cashbox->rp_va_mandiri = $rp_va_mandiri;

                $rp_dd_mandiri = $data_all;
                $rp_dd_mandiri = $rp_dd_mandiri->where('payment_method.code_name', 'pg_dd_mandiri');
                $rp_dd_mandiri = $rp_dd_mandiri->sum('sub_total') + $rp_dd_mandiri->sum('addon_total');
                $trans_cashbox->rp_dd_mandiri = $rp_dd_mandiri;

                $rp_va_bni = $data_all;
                $rp_va_bni = $rp_va_bni->where('payment_method.code_name', 'pg_va_bni');
                $rp_va_bni = $rp_va_bni->sum('sub_total') + $rp_va_bni->sum('addon_total');
                $trans_cashbox->rp_va_bni = $rp_va_bni;

                $rp_tav_qr = $data_all;
                $rp_tav_qr = $rp_tav_qr->where('payment_method.code_name', 'tav_qr');
                $rp_tav_qr = $rp_tav_qr->sum('sub_total') + $rp_tav_qr->sum('addon_total');
                $trans_cashbox->rp_tav_qr = $rp_tav_qr;

                $rp_link_aja = $data_all;
                $rp_link_aja = $rp_link_aja->where('payment_method.code_name', 'pg_link_aja');
                $rp_link_aja = $rp_link_aja->sum('sub_total') + $rp_link_aja->sum('addon_total');
                $trans_cashbox->rp_link_aja = $rp_link_aja;

                $rp_edc = $data_all;
                $rp_edc->where('payment_method.code_name', 'edc');
                $rp_edc = $rp_edc->sum('sub_total') + $rp_edc->sum('addon_total');
                $trans_cashbox->rp_edc = $rp_edc;

                $trans_cashbox->rp_addon_total = $data_all->sum('addon_total');
                $trans_cashbox->rp_total = $data_all->sum('sub_total') + $trans_cashbox->rp_addon_total;

                $trans_cashbox->save();

                // cek jika sudah ada tidak ada kasir yang open selain user ini maka toko tenant di tutup
                $cek = TransOperational::where('casheer_id', '!=', $user->id)
                    ->where('tenant_id', $user->tenant_id)
                    ->whereDay('start_date', '=', date('d'))
                    ->whereMonth('start_date', '=', date('m'))
                    ->whereYear('start_date', '=', date('Y'))
                    ->whereNull('end_date')
                    ->get();

                if ($cek->count() <= 0) {
                    $tenant = Tenant::find($user->tenant_id);
                    $tenant->update(['is_open' => 0]);
                }


                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Close cashier successfully',
                    'data' => TransCashbox::find($trans_cashbox->id)
                ]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error ' . $th->getMessage()
                ]);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ], 422);
    }
    public function getRating()
    {
        $user = auth()->user();
        $data = Tenant::when($id = $user->tenant_id, function ($q) use ($id) {
            return $q->where('id', $id);
        })->get();
        return response()->json(TsTenantResource::collection($data));
    }
    public function notifBukaTutupToko(User $user, $info){
        $data = User::where([['id', '!=', $user->id], ['tenant_id', $user->tenant_id], ['fcm_token', '!=', null], ['fcm_token', '!=', '']])->get();
        $ids = array();
        foreach ($data as $val) {
            if ($val['fcm_token'] != null && $val['fcm_token'] != '')
                array_push($ids, $val['fcm_token']);
        }

        if ($ids != '') {
            $payload = array(
                'id' => random_int(1000, 9999),
                'type' => 'action',
                'action' => 'refresh_buka_toko'
            );
            if($info=='tutup'){
                $result = sendNotif($ids, 'Pemberitahun', 'Pemberitahuan Toko anda di tutup sementara oleh ' . $user->name, $payload);
            }else
            if($info=='buka'){
                $result = sendNotif($ids, 'Pemberitahun', 'Pemberitahuan Toko anda sudah dibukan oleh ' . $user->name, $payload);
            }
            return $result;
        }
    }
    public function bukaToko(BukaTutupTenantRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->pin, $user->pin)) {
            $tenant = Tenant::findOrFail($user->tenant_id);
            $tenant->is_open = 1;
            $tenant->save();
            $result = $this->notifBukaTutupToko($user, 'buka');
            return response()->json([
                'status' => 'success',
                'message' => 'Open tenant '.$tenant->name.' successfully',
                'notif' => $result
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ], 422);
    }

    public function tutupToko(BukaTutupTenantRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->pin, $user->pin)) {
            $tenant = Tenant::findOrFail($user->tenant_id);
            $tenant->is_open = 0;
            $tenant->save();
            $result = $this->notifBukaTutupToko($user, 'tutup');
            return response()->json([
                'status' => 'success',
                'message' => 'Close tenant '.$tenant->name.' successfully',
                'notif' => $result
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ], 422);
    }
}
