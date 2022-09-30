<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
                'business_id' => $request->business_id,
                'merchant_id' => $request->merchant_id,
                'sub_merchant_id' => $request->sub_merchant_id,
                'tenant_id' => $request->tenant_id,
                'rest_area_id' => $request->rest_area_id,
                'paystation_id' => $request->paystation_id,
        ]);
        return response()->json($user); 
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        //$user->update($request->all());
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'business_id' => $request->business_id,
            'merchant_id' => $request->merchant_id,
            'sub_merchant_id' => $request->sub_merchant_id,
            'tenant_id' => $request->tenant_id,
            'rest_area_id' => $request->rest_area_id,
            'paystation_id' => $request->paystation_id,
        ]);
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json($user);
    }

    public function resetPin($id)
    {
        User::findOrfail($id)->update([
            'is_reset_pin' => 1,
        ]);
        return response()->json(['message' => 'Permintaan Reset PIN berhasil']);
    }
}
