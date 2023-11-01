<?php

namespace App\Http\Controllers\API;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserActivationRequest;
use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = User::when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($email = request()->email, function ($q) use ($email) {
            $q->where('email', 'like', '%' . $email . '%');
        })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when($status = request()->status, function ($q) use ($status) {
            return $q->where('status', $status);
        })->when($reset_pin = request()->reset_pin, function ($q) use ($reset_pin) {
            return $q->where('reset_pin', $reset_pin);
        })->when($role = request()->role, function ($q) use ($role) {
            return $q->where('role', $role);
        })->when($rest_area_id = request()->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })->when($sort = request()->sort, function ($q) use ($sort) {
            return $q->where('rest_area_id', $sort);
        })
        ->mySortOrder(request())
        ->get();
        return response()->json($data);
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $data = User::when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($email = request()->email, function ($q) use ($email) {
            $q->where('email', 'like', '%' . $email . '%');
        })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when($status = request()->status, function ($q) use ($status) {
            return $q->where('status', $status);
        })->when($reset_pin = request()->reset_pin, function ($q) use ($reset_pin) {
            return $q->where('reset_pin', $reset_pin);
        })->when($role = request()->role, function ($q) use ($role) {
            return $q->where('role', $role);
        })->when($rest_area_id = request()->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })->when($sort = request()->sort, function ($q) use ($sort) {
            return $q->where('rest_area_id', $sort);
        })
        ->mySortOrder(request())
        ->get();
        return Excel::download(new UserExport($data), 'User ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportReqPin()
    {
        $data = User::when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($email = request()->email, function ($q) use ($email) {
            $q->where('email', 'like', '%' . $email . '%');
        })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when($status = request()->status, function ($q) use ($status) {
            return $q->where('status', $status);
        })->when($reset_pin = request()->reset_pin, function ($q) use ($reset_pin) {
            return $q->where('reset_pin', $reset_pin);
        })->when($role = request()->role, function ($q) use ($role) {
            return $q->where('role', $role);
        })->when($rest_area_id = request()->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })->when($sort = request()->sort, function ($q) use ($sort) {
            return $q->where('rest_area_id', $sort);
        })
        ->mySortOrder(request())
        ->whereNotNull('reset_pin')
        ->get();
        return Excel::download(new UserExport($data), 'User Reset PIN ' . Carbon::now()->format('d-m-Y') . '.xlsx');
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

    public function approveResetPin($id)
    {
        $user = User::findOrfail($id);
        if ($user->reset_pin == User::WAITING_APPROVE) {
            $user->reset_pin = User::APPROVED;
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan Reset PIN berhasil disetujui'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak mempunyai permintaan reset PIN'
            ]);
        }
    }

    public function rejectResetPin($id)
    {
        $user = User::findOrfail($id);
        if ($user->reset_pin == User::WAITING_APPROVE) {
            $user->reset_pin = User::REJECT;
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan Reset PIN berhasil ditolak'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak mempunyai permintaan reset PIN'
            ]);
        }
    }

    public function activationUserCashier($id)
    {
        $user = User::where('id', $id)
            ->where('role', User::CASHIER)
            ->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ]);
        }
        $tenant = $user->tenant;
        $subscription = Subscription::where('super_merchant_id', $tenant->business_id)
            ->where('type', Subscription::OWNER)
            ->first();
        if (!$subscription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant tidak memiliki subscription'
            ]);
        }
        if ($subscription->status != Subscription::AKTIF) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subscription is not active'
            ]);
        }
        $user_tenant_count = User::where('tenant_id', $user->tenant_id)
            ->where('role', User::CASHIER)
            ->where('status', User::ACTIVE)
            ->count();
        if ($user_tenant_count >= $subscription->limit_cashier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant user cashier limit ' . $subscription->limit_cashier . ' reached '
            ]);
        }

        $user->status = User::ACTIVE;
        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diaktifkan'
        ]);
    }
}
