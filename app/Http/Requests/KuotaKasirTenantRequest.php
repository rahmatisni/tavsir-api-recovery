<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class KuotaKasirTenantRequest extends FormRequest
{
    protected $subsciption_aktif = 0;
    protected $sisa_kuota = 0;
    protected $kasir_aktif = 0;
    protected $min = 0;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $subsciption = Subscription::byOwner()->get();
        $this->subsciption_aktif = $subsciption->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier');
        $this->kasir_aktif = User::where('role', User::CASHIER)->where('tenant_id', $this->tenant_id)->where('is_subscription',1)->count();
        // $sum_limit =  $this->subsciption_aktif;
        // dump($sum_limit);

        $this->sisa_kuota = $this->subsciption_aktif - (Tenant::byOwner()->sum('kuota_kasir') - (Tenant::find($this->tenant_id ?? 0)?->kuota_kasir ?? 0));
        if($this->kuota_kasir < $this->sisa_kuota)
        {
            $this->min = $this->kasir_aktif;
        }       
        // dump($this->sisa_kuota);
        // dd('x');

        return [
            'tenant_id' => ['required','exists:ref_tenant,id,business_id,'.auth()->user()->business_id],
            'kuota_kasir' => ['required', 'integer', 'min:'.$this->min, 'max:'.$this->sisa_kuota]
        ];
    }

    public function messages()
    {
        return [
            'kuota_kasir.max' => 'Kuota kasir hanya '.$this->subsciption_aktif.'. Sisa limit kasir '.$this->sisa_kuota,
            'kuota_kasir.min' => 'Terdapat '.$this->kasir_aktif.' kasir aktif, silahkan non aktifkan kasir'
        ];
    }
}
