<?php

namespace App\Services;

use App\Models\CategoryTenant;

class CategoryTenantService extends BaseService
{
    protected $model;
    
    public function __construct(CategoryTenant $model = new CategoryTenant())
    {
        parent::__construct($model);
    }
}