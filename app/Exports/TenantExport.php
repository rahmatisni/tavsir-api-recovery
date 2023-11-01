<?php

namespace App\Exports;

use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TenantExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $record = Tenant::with('business','rest_area','ruas','order','category_tenant')->get();
        return TenantResource::collection($record);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Business id',
            'Business name',
            'Business owner name',
            'Business status perusahaan',
            'Rest area id',
            'Rest area name',
            'Rest area is open',
            'Ruas id',
            'Ruas name',
            'Name',
            'Category tenant id',
            'Category tenant name',
            'Address',
            'Latitude',
            'Longitude',
            'Time start',
            'Time end',
            'Phone',
            'Manager',
            'Photo url',
            'Url self order',
            'Merchant id',
            'Sub merchant id',
            'Is open',
            'Is verified',
            'In takengo',
            'Is print',
            'Is scan',
            'Is composite',
            'List payment',
            'Rating',
            'Total rating',
            'Created by',
            'Created at',
            'Updated at',
        ];
    }
}
