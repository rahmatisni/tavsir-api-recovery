<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class BaseService
{
    protected $model = Model::class;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function baseAll()
    {
        return $this->model->all();
    }

    public function baseCreate(array $data)
    {
        return $this->model->create($data);
    }

    public function baseFind($id)
    {
        return $this->model->findOrFail($id);
    }

    public function baseFindByKey(array $key)
    {
        return $this->model->where($key)->first();
    }

    public function baseWhere(array $key)
    {
        return $this->model->where($key)->get();
    }

    public function baseUpdate($id, $data)
    {
        $model = $this->baseFind($id);
        $model->update($data);
        return $model;
    }

    public function baseDelete($id)
    {
        if(!is_array($id)){
            $id = [$id];
        }
        return $this->model->whereIn('id', $id)->delete();
    }
}