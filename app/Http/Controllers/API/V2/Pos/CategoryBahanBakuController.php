<?php

namespace App\Http\Controllers\API\V2\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CategoryBahanBakuRequest;
use App\Http\Resources\Pos\CategoryResource;
use App\Models\User;
use App\Services\Pos\CategoryBahanBakuServices;

class CategoryBahanBakuController extends Controller
{
    public function __construct(protected CategoryBahanBakuServices $service)
    {
        $this->middleware('role:'.User::TENANT.','.User::CASHIER);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->responsePaginate(CategoryResource::class, $this->service->list(request()->search, request()->filter));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryBahanBakuRequest $request)
    {
       return $this->response($this->service->create($request->validated()));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->response($this->service->show($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\CategoryBahanBakuRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update($id, CategoryBahanBakuRequest $request)
    {
        return $this->response($this->service->update($id,$request->validated()));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // return $this->response($this->service->delete($id));
        $data = $this->service->delete($id);
        if($data == false){
            return response()->json(['message' => 'Kategori tidak dapat dihapus karna sudah digunakan pada produk'], 422);
        }
        return response()->json(['message' => 'Kategori bahan baku berhasil dihapus!'], 200);


    }
}
