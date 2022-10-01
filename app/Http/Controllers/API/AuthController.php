<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PinRequest;
use App\Http\Requests\PinStoreRequest;
use App\Http\Resources\ProfileResource;
use App\Models\TransOperasional;
use App\Models\TransOperational;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $tokenResult = $user->createToken('Personal');
                $token = $tokenResult->accessToken;
                $response = [
                    'access_token' => $token,
                ];
                return response()->json($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
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
        if($user->pin != null && $user->reset_pin != User::APPROVED)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Atur ulang PIN belum di setujui'
            ], 422);
        }
        $user->pin = bcrypt($request->pin);
        $user->reset_pin = null;
        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Autut ulang PIN successfully'
        ]);
    }

    public function openCashier(PinRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->pin, $user->pin))
        {
            $cek = TransOperational::where('casheer_id', $user->id)
                            ->where('tenant_id', $user->tenant_id)
                            ->whereNull('end_date')
                            ->first();
            if($cek){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silahkan tutup kasir terlebih dahulu'
                ], 422);
            }

            TransOperational::create([
                'tenant_id' => $user->tenant_id,
                'casheer_id' => $user->id,
                'start_date' => Carbon::now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Open cashier successfully'
            ]);
        }

        

        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ],422);
    }

    public function closeCashier(PinRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->pin, $user->pin))
        {
            $data = TransOperational::where('casheer_id', $user->id)
                            ->where('tenant_id', $user->tenant_id)
                            ->whereNull('end_date')
                            ->first();
            if(!$data){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silahkan buka kasir terlebih dahulu'
                ], 422);
            }
            
            $periode = TransOperational::where('tenant_id', $user->tenant_id)
                            ->whereNotNull('end_date')
                            ->where('start_date',Carbon::now()->format('Y-m-d'))
                            ->count() + 1;

            $end_date = Carbon::now();             
            $data->periode = $periode;
            $data->duration = $data->start_date->diffInSeconds($end_date);
            $data->end_date = $end_date;
            $data->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Close cashier successfully'
            ]);
        }

        

        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ],422);
    }
}
