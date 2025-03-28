<?php

namespace App\Http\Resources;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = auth()->user();
        $business_id = 0;
        $logo = $user->tenant->logo ?? null;
        $additional_data = [];
        if(in_array($user->role,[User::OWNER, User::TENANT, User::CASHIER])){
            switch ($user->role) {
                case User::OWNER:
                    $business_id = $user->business->id ?? 0;
                    break;
                case User::TENANT:
                    $business_id = $user->tenant->business->id ?? 0;
                    $additional_data = [
                        'logo' => $logo ? asset($logo) : null,
                        'additional_information' => $user->tenant->additional_information ?? null,
                        'instagram' => $user->tenant->instagram ?? null,
                        'facebook' => $user->tenant->facebook ?? null,
                        'website' => $user->tenant->website ?? null,
                        'note' => $user->tenant->note ?? null,
                        'business_name' => $user->tenant->business->name ?? null,
                    ];
                    break;
                case User::CASHIER:
                    $tenant = User::where('tenant_id', $user->tenant->id)->firstOrFail();
                    $business_id = $user->tenant->business->id ?? 0;
                    break;
                
                default:
                    # code...
                    break;
            }
        }

        $subscription_end = Business::find($business_id)?->subscription_end;
        if($subscription_end){
            $subscription_end = Carbon::parse($subscription_end)->diffForHumans();
        }
        // $print = Tenant::find($this->tenant_id);
        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'photo' => $this->photo ? asset($this->photo) : null,
            'is_admin' => $this->is_admin,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? '',
            'category_tenant_name' => $this->tenant->category_tenant->name ?? '',
            'tenant_phone' => $this->tenant->phone ?? '',
            'tenant_is_open' => $this->tenant->is_open ?? '',
            'supertenant_name' => $this->supertenant?->name ?? '',
            'business_id' => $this->business_id,
            'merchant_id' => $this->merchant_id,
            'sub_merchant_id' => $this->sub_merchant_id,
            'rest_area_id' => $this->rest_area_id,
            'rest_area_name' => $this->tenant?->rest_area?->name ?? '',
            'paystation_id' => $this->paystation_id,
            'paystation_name' => $this->paystation?->name ?? '',
            'jabatan' => 'Karyawan',
            'role' => $this->role,
            'status' => $this->status,
            'have_pin' => $this->pin ? true : false,
            'reset_pin' => $this->reset_pin,
            'fcm_token' => $this->fcm_token,
            'subscription_end' => $subscription_end,
            'is_print' =>  $this->tenant->is_print ?? 0,
            'is_scan'  => $this->tenant->is_scan ?? 0,
            'is_composite' => $this->tenant->is_composite ?? 0,
            'in_takengo' => $this->tenant->in_takengo ?? 0,
            'in_selforder' => ($this->tenant?->in_selforder > 0 ? 1:0) ?? 0, 
            'list_payment' => $this->tenant->list_payment ?? ['2'], 
            'is_supertenant' => $this->tenant->is_supertenant ?? null,
            'tenant_mail' => $this->tenant?->email ?? null,
            'is_derek' => $this->tenant?->is_derek ?? null

        ], $additional_data);
    }
}
