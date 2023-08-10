<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class KuotaKasirTenantRequest extends FormRequest
{
    protected $subsciption_aktif = 0;
    protected $sisa_kuota = 0;
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
        // $sum_limit =  $this->subsciption_aktif;
        // dump($sum_limit);

        $this->sisa_kuota = $this->subsciption_aktif - Tenant::byOwner()->sum('kuota_kasir');
       
        // dump($this->sisa_kuota);
        // dd('x');

        return [
            'tenant_id' => ['required','exists:ref_tenant,id,business_id,'.auth()->user()->business_id],
            'kuota_kasir' => ['required', 'integer', 'min:0', 'max:'.$this->sisa_kuota]
        ];
    }

    public function messages()
    {
        return [
            'kuota_kasir.max' => 'Kuota kasir hanya '.$this->subsciption_aktif.'. Sisa limit kasir '.$this->sisa_kuota
        ];
    }
}
