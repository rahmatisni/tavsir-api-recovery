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
        TransOperational::create([
            'tenant_id' => $user->tenant_id,
            'periode' => 1,
            'casheer_id' => $user->id,
            'start_date' => Carbon::now(),
            'duration' => 0,
        ]);
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
            return response()->json([
                'status' => 'success',
                'message' => 'PIN verified successfully'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'PIN verification failed'
        ],422);
    }
}
