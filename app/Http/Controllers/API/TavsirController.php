<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TsCreatePaymentRequest;
use App\Http\Resources\SaldoResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusOrderReqeust;
use App\Http\Requests\CloseTenantSupertenantRequest;
use App\Http\Requests\ConfirmOrderMemberSupertenantRequest;
use App\Http\Requests\PaymentOrderRequest;
use App\Http\Requests\TavsirProductRequest;
use App\Models\Bind;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tavsir\TrOrderRequest;
use App\Http\Requests\Tavsir\TrCategoryRequest;
use App\Http\Requests\TavsirChangeStatusProductRequest;
use App\Http\Requests\TsOrderConfirmRequest;
use App\Http\Requests\VerifikasiOrderReqeust;
use App\Http\Resources\BaseResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Tavsir\OrderSupertenantRefundResource;
use App\Http\Resources\Tavsir\OrderSupertenantResource;
use App\Http\Resources\Tavsir\ProductSupertenantResource;
use App\Http\Resources\Tavsir\TrProductResource;
use App\Http\Resources\Tavsir\TrCartSavedResource;
use App\Http\Resources\Tavsir\TrOrderResource;
use App\Http\Resources\TravShop\TsOrderResource;
use App\Http\Resources\Tavsir\TrCategoryResource;
use App\Http\Resources\Tavsir\TrOrderSupertenantResource;
use App\Models\AddonPrice;
use App\Models\Bank;
use App\Models\Product;
use App\Models\Category;
use App\Models\Constanta\ProductType;
use App\Models\ExtraPrice;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransEdc;
use App\Models\TransOrderArsip;
use App\Models\TransPayment;
use App\Models\TransSaldo;
use App\Models\TransSharing;
use App\Models\TransStock;
use App\Models\Voucher;
use App\Services\StockServices;
use App\Services\TransSharingServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TavsirController extends Controller
{
    protected $stock_service;
    protected $trans_sharing_service;

    public function __construct(StockServices $stock_service, TransSharingServices $trans_sharing_service)
    {
        $this->stock_service = $stock_service;
        $this->trans_sharing_service = $trans_sharing_service;
    }

    public function tenantSupertenantList(Request $request)
    {
        $data = auth()->user()->supertenant?->tenant;
        return response()->json(BaseResource::collection($data));
    }

    public function closeTenantSupertenant(CloseTenantSupertenantRequest $request)
    {
        $data = auth()->user()->supertenant?->tenant;
        if (count($data) > 0) {
            if ($request->tenant_id != 'all') {
                $data = $data->where('id', $request->tenant_id);
            }
            $data->each(function ($item) {
                $item->is_open = 0;
                $item->save();
                //SEND NOTIF
            });
        }

        return response()->json(BaseResource::collection($data));
    }

    public function productSupertenantList(Request $request)
    {
        $data = Product::bySupertenant()->byType(ProductType::PRODUCT)->with('tenant')->when($filter = $request->filter, function ($q) use ($filter) {
            return $q->where('name', 'like', "%$filter%")
                ->orwhere('sku', 'like', "%$filter%");
        })->when($category_id = $request->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        });
        if ($request->is_active == '0') {
            $data->where('is_active', '0');
        } else if ($request->is_active == '1') {
            $data->where('is_active', '1');
        }
        $data = $data->orderBy('updated_at', 'desc')->get();
        return response()->json(ProductSupertenantResource::collection($data));
    }

    public function orderSuperTenant(TrOrderRequest $request)
    {
        try {
            $data = TransOrder::find($request->id);

            DB::beginTransaction();
            if (!$data) {
                $data = new TransOrder();
                $data->order_type = TransOrder::POS;
                $data->order_id = (auth()->user()->supertenant?->rest_area_id ?? '0') . '-' . (auth()->user()->supertenant_id ?? '0') . '-STAV-' . date('YmdHis');
                $data->status = TransOrder::CART;
            }
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE) {
                return response()->json(['message' => 'Order status ' . $data->statusLabel()], 400);
            }
            $data->rest_area_id = auth()->user()->supertenant?->rest_area_id ?? null;
            $data->supertenant_id = auth()->user()->supertenant_id;
            $data->business_id = auth()->user()->business_id;
            $data->casheer_id = auth()->user()->id;
            $data->detil()->delete();
            $order_detil_many = [];
            $data->save();

            $sub_total = 0;
            foreach ($request->product as $k => $v) {
                $product = Product::byType(ProductType::PRODUCT)->find($v['product_id']);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->status = TransOrderDetil::STATUS_WAITING;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->base_price = $product->price;
                $order_detil->price = $product->price;
                $customize_x = array();
                foreach ($v['customize'] as $key => $value) {
                    $customize_y = collect($product->customize)->where('id', $value)->first();
                    if ($customize_y) {
                        $pilihan = collect($customize_y->pilihan);
                        $pilihan = $pilihan->where('id', $v['pilihan'][$key])->first();
                        if ($pilihan) {
                            $customize_z = [
                                'customize_id' => $customize_y->id,
                                'customize_name' => $customize_y->name,
                                'pilihan_id' => $pilihan->id,
                                'pilihan_name' => $pilihan->name,
                                'pilihan_price' => $pilihan->price,
                            ];
                            $customize_x[] = $customize_z;
                            $order_detil->price += $pilihan->price;
                        }
                    }
                }
                $order_detil->customize = json_encode($customize_x);
                $order_detil->qty = $v['qty'];
                $order_detil->total_price = $order_detil->price * $v['qty'];
                $order_detil->note = $v['note'];

                $sub_total += $order_detil->total_price;

                $order_detil_many[] = $order_detil;
            }

            $data->sub_total = $sub_total;
            $data->total = $data->sub_total + $data->fee + $data->service_fee;

            $data->save();
            $data->detil()->saveMany($order_detil_many);

            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function orderListSupertenant(Request $request)
    {
        $data = TransOrder::byRole()->when($status = request()->status, function ($q) use ($status) {
            if (is_array($status)) {
                $q->whereIn('status', $status);
            } else {
                $q->where('status', $status);
            }
        })
            ->when($start_date = $request->start_date, function ($q) use ($start_date) {
                $q->whereDate('created_at', '>=', date("Y-m-d", strtotime($start_date)));
            })
            ->when($end_date = $request->end_date, function ($q) use ($end_date) {
                $q->whereDate('created_at', '<=', date("Y-m-d", strtotime($end_date)));
            })
            ->when($statusnot = request()->statusnot, function ($q) use ($statusnot) {
                if (is_array($statusnot)) {
                    $q->whereNotIn('status', $statusnot);
                } else {
                    $q->whereNotIn('status', $statusnot);
                }
            })
            ->when($filter = request()->filter, function ($q) use ($filter) {
                return $q->where('order_id', 'like', "%$filter%");
            })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            $q->where('tenant_id', $tenant_id);
        })->when($order_type = request()->order_type, function ($q) use ($order_type) {
            $q->where('order_type', $order_type);
        })->when($sort = request()->sort, function ($q) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $val) {
                    $jsonx = explode("&", $val);
                    $q->orderBy($jsonx[0], $jsonx[1]);
                }
            }
        });
        if (!request()->sort) {
            $data = $data->orderBy('created_at', 'desc');
        }
        $data = $data->get();
        return response()->json(OrderSupertenantResource::collection($data));
    }

    public function orderByIdSupertenant($id)
    {
        $data = TransOrder::byRole()->findOrfail($id);
        return response()->json(new OrderSupertenantResource($data));
    }

    public function orderListMemberSupertenant(Request $request)
    {
        $tenant_user = auth()->user()->tenant;
        $data = TransOrder::with('detil.product.tenant')
            ->whereIn('status', [TransOrder::CART, TransOrder::PAYMENT_SUCCESS, TransOrder::DONE])
            ->where('supertenant_id', $tenant_user->supertenant_id ?? 0)
            ->whereHas('detil', function ($q) use ($tenant_user) {
                $q->whereHas('product', function ($qq) use ($tenant_user) {
                    $qq->where('tenant_id', $tenant_user->id ?? 0);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(TrOrderSupertenantResource::collection($data));
    }

    public function orderByIdMemberSupertenant($id)
    {
        $tenant_user = auth()->user()->tenant;
        $data = TransOrder::with('detil.product.tenant')
            ->whereIn('status', [TransOrder::CART, TransOrder::PAYMENT_SUCCESS, TransOrder::DONE])
            ->where('supertenant_id', $tenant_user->supertenant_id ?? 0)
            ->whereHas('detil', function ($q) use ($tenant_user) {
                $q->whereHas('product', function ($qq) use ($tenant_user) {
                    $qq->where('tenant_id', $tenant_user->id ?? 0);
                });
            })
            ->findOrfail($id);
        return response()->json(new TrOrderSupertenantResource($data));
    }

    public function confirmOrderMemberSupertenant(ConfirmOrderMemberSupertenantRequest $request)
    {
        $tenant_user = auth()->user()->tenant;
        $data = TransOrderDetil::whereHas('product', function ($qq) use ($tenant_user) {
            $qq->where('tenant_id', $tenant_user->id ?? 0);
        })->where('id', $request->detil_id)->first();
        if (!$data) {
            return response()->json([
                'message' => 'Data Not Found'
            ], 404);
        }
        if ($data->status != TransOrderDetil::STATUS_WAITING) {
            return response()->json([
                'message' => 'Cant change Status order not ' . TransOrderDetil::STATUS_WAITING
            ], 400);
        }
        $data->status = $request->status;
        $data->save();

        return response()->json([
            'message' => 'Succes confirm ' . $data->status
        ]);
    }

    public function doneOrderMemberSupertenant($id)
    {
        $tenant_user = auth()->user()->tenant;
        $data = TransOrderDetil::whereHas('product', function ($qq) use ($tenant_user) {
            $qq->where('tenant_id', $tenant_user->id ?? 0);
        })->where('id', $id)->first();
        if (!$data) {
            return response()->json(404, [
                'message' => 'Data Not Found'
            ]);
        }
        if ($data->status != TransOrderDetil::STATUS_READY) {
            return response()->json([
                'message' => 'Cant change Status order not ' . TransOrderDetil::STATUS_READY
            ], 400);
        }
        $data->status = TransOrderDetil::STATUS_DONE;
        $data->save();
        return response()->json([
            'message' => 'Succes order ' . $data->status
        ]);
    }

    public function orderRefund($id)
    {
        $data = TransOrder::byRole()->findOrfail($id);
        if ($data->is_refund) {
            return response()->json([
                'message' => 'Order sudah di refund'
            ], 400);
        }
        $status = $data->detil->pluck('status')->toArray();
        $result = array_intersect($status, [null, TransOrderDetil::STATUS_WAITING]);
        if (!empty($result)) {
            return response()->json([
                'message' => 'Terdapat order yang belum dikonfirmasi tenant'
            ], 400);
        }
        $total_refund = 0;
        $order_refund = $data->detil->where('status', TransOrderDetil::STATUS_CANCEL);
        if ($order_refund->count() == 0) {
            return response()->json([
                'message' => 'Tidak ada order yang di Cancel'
            ], 400);
        }
        foreach ($order_refund as $v) {
            $total_refund += $v->total_price;
        }
        $data->sub_total = $data->sub_total - $total_refund;
        $data->total = $data->sub_total + $data->fee;
        $data->is_refund = 1;
        $data->save();
        $data->payment->data = [
            'cash' => $data->pay_amount,
            'total' => $data->total,
            'kembalian' => $data->pay_amount - $data->total
        ];
        $data->payment->save();

        return response()->json([
            'message' => 'Refund sebesar ' . $total_refund
        ]);
    }

    public function productList(Request $request)
    {
        $data = Product::byTenant()->byType(ProductType::PRODUCT)->with('tenant')->when($filter = $request->filter, function ($q) use ($filter) {
            $q->where(function ($qq) use ($filter) {
                return $qq->where('name', 'like', "%$filter%")
                    ->orwhere('sku', 'like', "%$filter%");
            });
        })->when($category_id = $request->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        });
        if ($request->is_active == '0') {
            $data->where('is_active', '0');
        } else if ($request->is_active == '1') {
            $data->where('is_active', '1');
        }
        $data = $data->orderByRaw('stock = 0')->orderByRaw('is_active = 0')->orderBy('name', 'asc')->get();
        return response()->json(TrProductResource::collection($data));
    }

    public function productStore(TavsirProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new Product();
            $data->tenant_id = auth()->user()->tenant_id;
            $data->fill($request->all());
            $data->type = ProductType::PRODUCT;
            $data->save();
            $data->trans_stock()->create([
                'stock_type' => TransStock::INIT,
                'recent_stock' => 0,
                'stock_amount' => $data->stock,
                'created_by' => auth()->user()->id,
            ]);
            $data->customize()->sync($request->customize);
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function productShow(Product $id)
    {
        return response()->json(new ProductResource($id));
    }

    public function updateStatusProduct(TavsirChangeStatusProductRequest $request)
    {
        $product = Product::byTenant()->byType(ProductType::PRODUCT)->whereIn('id', $request->product_id);
        if ($product->count() == 0) {
            return response()->json(['message' => 'Not Found.'], 404);
        }
        $product->update(['is_active' => $request->is_active]);

        return response()->json($product->get());
    }

    public function productById($id)
    {
        $data = Product::byType(ProductType::PRODUCT)->findOrfail($id);
        return response()->json(new ProductResource($data));
    }

    public function productUpdate(TavsirProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();
            $product->fill($request->all());
            $product->tenant_id = auth()->user()->tenant_id;
            $product->save();
            $product->customize()->sync($request->customize);
            DB::commit();
            return response()->json($product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function productDestroy(Product $product)
    {
        if (request()->ids) {
            $product->whereIn('id', request()->ids)->delete();
            return response()->json($product);
        } else {
            $product->delete();
            return response()->json($product);
        }
    }

    public function categoryList(Request $request)
    {
        $data = Category::byType(ProductType::PRODUCT)->byTenant()->when($filter = $request->filter, function ($q) use ($filter) {
            return $q->where('name', 'like', "%$filter%");
        })->orderBy('name')->get();
        return response()->json(TrCategoryResource::collection($data));
    }

    public function categoryStore(TrCategoryRequest $request)
    {
        $data = new Category();
        $data->fill($request->all());
        $data->tenant_id = auth()->user()->tenant_id;
        $data->save();
        return response()->json($data);
    }

    public function categoryShow(Category $category)
    {
        return response()->json($category);
    }

    public function categoryUpdate(TrCategoryRequest $request, Category $category)
    {
        $category->update($request->all());
        $category->tenant_id = auth()->user()->tenant_id;
        return response()->json($category);
    }

    public function categoryDestroy(Category $category)
    {
        $data = Product::byType(ProductType::PRODUCT)->where('category_id', $category->id)->count();
        if ($data > 0) {
            return response()->json(['message' => 'Kategori tidak dapat dihapus karna sudah digunakan pada produk'], 422);
        }
        $category->delete();
        return response()->json($category);
    }

    public function order(TrOrderRequest $request)
    {
        try {
            $data = TransOrder::find($request->id);

            DB::beginTransaction();
            if (!$data) {
                $data = new TransOrder();
                $data->order_type = TransOrder::POS;
                $data->order_id = (auth()->user()->tenant->rest_area_id ?? '0') . '-' . (auth()->user()->tenant_id ?? '0') . '-POS-' . date('YmdHis');
                $data->status = TransOrder::CART;
                $data->nomor_name = $request->nomor_name;

            }
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE) {
                return response()->json(['message' => 'Order status ' . $data->statusLabel()], 400);
            }
            $data->rest_area_id = auth()->user()->tenant->rest_area_id ?? null;
            $data->tenant_id = auth()->user()->tenant_id;
            $data->business_id = auth()->user()->business_id;
            $data->casheer_id = auth()->user()->id;
            $data->nomor_name = $request->nomor_name;

            $data->detil()->delete();
            $order_detil_many = [];
            $data->save();

            $sub_total = 0;
            foreach ($request->product as $k => $v) {
                $product = Product::byType(ProductType::PRODUCT)->find($v['product_id']);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->base_price = $product->price;
                $order_detil->price = $product->price;
                $customize_x = array();
                foreach ($v['customize'] as $key => $value) {
                    $customize_y = collect($product->customize)->where('id', $value)->first();
                    if ($customize_y) {
                        $pilihan = collect($customize_y->pilihan);
                        $pilihan = $pilihan->where('id', $v['pilihan'][$key])->first();
                        if ($pilihan) {
                            $customize_z = [
                                'customize_id' => $customize_y->id,
                                'customize_name' => $customize_y->name,
                                'pilihan_id' => $pilihan->id,
                                'pilihan_name' => $pilihan->name,
                                'pilihan_price' => $pilihan->price,
                            ];
                            $customize_x[] = $customize_z;
                            $order_detil->price += $pilihan->price;
                        }
                    }
                }
                $order_detil->customize = json_encode($customize_x);
                $order_detil->qty = $v['qty'];
                $order_detil->total_price = $order_detil->price * $v['qty'];
                $order_detil->note = $v['note'];

                $sub_total += $order_detil->total_price;

                $order_detil_many[] = $order_detil;
            }

            $extra_price = ExtraPrice::byTenant($data->tenant_id)->aktif()->get();
            $data->addon_total = 0;
            $data->addon_price()->delete();
            foreach ($extra_price as $value) {
                $addon = new AddonPrice();
                $addon->trans_order_id = $data->id;
                $addon->name = $value->name;
                $addon->price = $value->price;
                if ($value->is_percent == 1) {
                    $addon->price = ($sub_total * $value->price) / 100;
                }
                $addon->save();
                $data->addon_total += $addon->price;
            }

            $data->sub_total = $sub_total;
            $data->total = $data->sub_total + $data->fee + $data->service_fee + $data->addon_total;

            $data->save();
            $data->detil()->saveMany($order_detil_many);
            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function countNewTNG()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
            ->count();

        return response()->json(['count' => $data]);
    }

    public function countCarSaved()
    {
        $data = TransOrder::byRole()
            ->where('order_type', '=', TransOrder::POS)
            ->where('status', '=', TransOrder::CART)
            ->count();

        return response()->json(['count' => $data]);
    }

    public function cartDelete(Request $request)
    {
        $data = TransOrder::whereIn('id', $request->id)
            ->where('tenant_id', '=', auth()->user()->tenant_id)
            ->where('order_type', '=', TransOrder::POS)
            ->where('status', '=', TransOrder::CART)
            ->get();

        $deleteDetail = TransOrderDetil::whereIn('trans_order_id', $request->id)->delete();
        $data->each->delete();

        return response()->json($data);
    }

    public function orderConfirm(TsOrderConfirmRequest $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        if ($data->status != TransOrder::WAITING_CONFIRMATION_TENANT && $data->status != TransOrder::WAITING_OPEN) {
            return response()->json(['message' => 'Order status' . $data->statusLabel()], 400);
        }

        $data->detil->whereNotIn('id', $request->detil_id)->each(function ($item) {
            $item->delete();
        });

        $data->sub_total = $data->detil->whereIn('id', $request->detil_id)->sum('total_price');
        // $data->total = $data->sub_total + $data->fee + $data->service_fee;
        $data->total = $data->sub_total + $data->fee + $data->service_fee + $data->addon_total;
        $data->casheer_id = auth()->user()->id;
        $data->status = TransOrder::WAITING_CONFIRMATION_USER;
        $data->confirm_date = Carbon::now();
        $data->save();

        $data = TransOrder::findOrfail($id);
        if ($data->order_type == TransOrder::ORDER_TRAVOY) {
            $product_kios = ProductKiosBank::select(
                'kategori',
                'sub_kategori',
                'kode',
                'name'
            )->get();
            $data->map(function ($i) use ($product_kios) {
                $i->getProductKios = $product_kios;
            });
        }
        return response()->json(new TsOrderResource($data));
    }

    public function bank()
    {
        return response()->json(Bank::all());
    }

    // public function paymentMethod(Request $request)
    // {
    //     //$reques->trans_order_Id
    //     // if(!$data->is_verified){
    //     //cek ke PG
    //     //set verified
    //     // }

    //     //if(verified)
    //     //show payment method fro pg

    //     $paymentMethods = PaymentMethod::all();
    //     $tavsir = ['1','2','3','5','7','9','10'];


    //     foreach ($paymentMethods as $key => $value) {
    //         if ($value->sof_id && $request->amount) {
    //             $data = PgJmto::tarifFee($value->sof_id, $value->payment_method_id, $value->sub_merchant_id, $request->amount);
    //             $value->fee = $data;
    //         }
    //         if (in_array($value->id, $tavsir)){
    //             $value->tavsir = true;
    //         }
    //         else {
    //             $value->tavsir = false;
    //         }

    //         if ($value->sof_id == (0||null)) {
    //             $value->status = false;
    //         }
    //         if ($value->sof_id != 0) {
    //             $value->status = true;
    //         }
    //     }
    //     return response()->json($paymentMethods);
    // }

    public function paymentMethod(Request $request)
    {
        $paymentMethods = PaymentMethod::all();
        $removes = [];
        $remove = [];

        $self_order = ['5', '7', '9'];
        $travshop = ['5', '6', '7', '8', '9', '10'];
        $tavsir = ['1', '2', '3', '10'];


        if ($request->trans_order_id) {
            $trans_order = TransOrder::with('tenant')->findOrfail($request->trans_order_id);

            $param_removes = Tenant::where('id', $trans_order->tenant_id)->firstOrFail();
            $removes = json_decode($param_removes->list_payment);

            $tenant = $trans_order->tenant;
            $tenant_is_verified = $tenant?->is_verified;

            if ($tenant_is_verified === false && $trans_order->order_type != TransOrder::ORDER_TRAVOY) {
                $merchant = PgJmto::listSubMerchant();
                if ($merchant->successful()) {
                    $merchant = $merchant->json();
                    if ($merchant['status'] == 'success') {
                        $merchant = $merchant['responseData'];
                        foreach ($merchant as $key => $value) {
                            if ($value['email'] == $tenant->email) {
                                $trans_order->tenant()->update([
                                    'is_verified' => 1,
                                    'sub_merchant_id' => $value['merchant_id']
                                ]);
                            }
                        }
                    }
                }
            }


            foreach ($paymentMethods as $value) {
                $value->platform_fee = env('PLATFORM_FEE');
                $value->fee = 0;
                $value->self_order = false;
                $value->travshop = false;
                $value->tavsir = false;

                if (in_array($value->id, $self_order)) {
                    $value->self_order = true;
                }

                if (in_array($value->id, $travshop)) {
                    $value->travshop = true;
                }
                if (in_array($value->id, $tavsir)) {
                    $value->tavsir = true;
                }

                // if (!in_array($value->id, $removes)) {
                //     $value->self_order = false;
                //     $value->travshop = false;
                //     $value->tavsir = false;
                // }

                if ($value->sof_id) {
                    // tenant_is_verified
                    // if ($tenant_is_verified || $trans_order->order_type == TransOrder::ORDER_TRAVOY) {

                    $data = PgJmto::tarifFee($value->sof_id, $value->payment_method_id, $value->sub_merchant_id, $trans_order->sub_total);
                 
                    $value->percentage = $data['is_presentage'] ?? null;
                    $x = $data['value'] ?? 'x';
                    $state = $data['is_presentage'] ?? null;


                    if ($state == (false || null)) {
                        $value->fee = $data['value'] ?? null;
                    } else {
                        $value->fee = (int) ceil((float) $x / 100 * $trans_order->sub_total);
                    }
                }

            }
            dd($paymentMethods);

        }

        // $merchant = PgJmto::listSubMerchant();
        // log::info($merchant);

        $paymentMethods = $paymentMethods->whereNotIn('id', $remove);
        return response()->json($paymentMethods);
    }


    public function createPayment(TsCreatePaymentRequest $request, $id)
    {
        $payment_payload = [];
        $data = TransOrder::findOrfail($id);

        try {
            DB::beginTransaction();
            // if (request()->order_from_qr == true) {
            //     if ($data->status == TransOrder::CART || $data->status == TransOrder::PENDING || $data->status == null) {
            //         $data->status = TransOrder::WAITING_PAYMENT;
            //         $data->customer_id = $request->customer_id;
            //     }
            // }
            // if ($data->status == TransOrder::QUEUE) {
            //     // $data->total = $data->sub_total;
            //     // $data->save();
            //     // DB::commit();
            //     $data->status = TransOrder::WAITING_PAYMENT;
            // }

            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::READY || $data->status == TransOrder::DONE) {

                return response()->json(['info' => $data->status], 422);
            }
            //Cek deposit
            // if ($data->order_type == TransOrder::ORDER_TRAVOY) {
            //     $deposit = $this->kiosBankService->cekDeposit();
            //     if ($deposit['rc'] == '00') {
            //         if ((int) $deposit['deposit'] < $data->sub_total) {
            //             return response()->json(['info' => 'Deposit ' . $deposit['deposit'] . ' < ' . $data->sub_total], 422);
            //         }
            //     } else {
            //         return response()->json(['info' => 'Deposit ', 'data' => $deposit], 422);
            //     }
            // }

            $data->payment_method_id = $request->payment_method_id;

            $res = 'Invalid';
            $payment_method = PaymentMethod::find($request->payment_method_id);
            if ($data->order_type != TransOrder::ORDER_TRAVOY) {
                $data->total = $data->sub_total + $data->addon_total + $data->fee;
            }
            switch ($payment_method->code_name) {
                case 'pg_va_mandiri':
                    $payment_payload = [
                        "sof_code" => $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'GetPay',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" => "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id,
                    ];

                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'GetPay',
                        $data->total,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name,
                        $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->status = TransOrder::WAITING_PAYMENT;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                case 'pg_va_bri':
                    $payment_payload = [
                        "sof_code" => $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'GetPay',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" => "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id,
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'GetPay',
                        $data->total,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name,
                        $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->status = TransOrder::WAITING_PAYMENT;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                case 'pg_va_bni':
                    $payment_payload = [
                        "sof_code" => $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'GetPay',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" => "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id,
                    ];

                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'GetPay',
                        $data->total,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name,
                        $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->status = TransOrder::WAITING_PAYMENT;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                // case 'tav_qr':
                //     $voucher = Voucher::where('hash', request()->voucher)
                //         ->where('is_active', 1)
                //         ->where('rest_area_id', $data->tenant->rest_area_id)
                //         ->first();

                //     if (!$voucher) {
                //         return response()->json(['error' => 'Voucher tidak ditemukan'], 500);
                //     }

                //     if ($voucher->balance < $data->total) {
                //         return response()->json(['error' => 'Ballance tidak cukup'], 500);
                //     }

                //     $balance_now = $voucher->balance;
                //     $voucher->balance -= $data->total;
                //     $ballaceHistory = [
                //         "trx_id" => $data->id,
                //         "trx_order_id" => $data->order_id,
                //         "trx_type" => 'Belanja',
                //         "trx_area" => $data->tenant ? ($data->tenant->rest_area ? $data->tenant->rest_area->name : '') : '',
                //         "trx_name" => $data->tenant ? $data->tenant->name : '',
                //         "trx_amount" => $data->total,
                //         "current_balance" => $voucher->balance,
                //         "last_balance" => $balance_now,
                //         "datetime" => Carbon::now()->toDateTimeString(),
                //     ];
                //     $dataHistori = $voucher->balance_history;
                //     $dataHistori['data'] = array_merge([$ballaceHistory], $voucher->balance_history['data']);
                //     $dataHistori['current_balance'] = $voucher->balance;
                //     $voucher->balance_history = $dataHistori;
                //     $voucher->qr_code_use = $voucher->qr_code_use + 1;
                //     $voucher->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                //     $voucher->save();

                //     $payment_payload = [
                //         'order_id' => $data->order_id,
                //         'order_name' => 'GetPay',
                //         'amount' => $data->total,
                //         'desc' => $data->tenant->name ?? 'Travoy',
                //         'phone' => $request->customer_phone,
                //         'email' => $request->customer_email,
                //         'customer_name' => $request->customer_name,
                //         'voucher' => $voucher->id
                //     ];
                //     $payment = new TransPayment();
                //     $payment->trans_order_id = $data->id;
                //     $payment->data = $payment_payload;
                //     $data->payment()->save($payment);
                //     $data->total = $data->total + $data->service_fee + $data->addon_total;
                //     $data->status = TransOrder::PAYMENT_SUCCESS;
                //     $data->save();
                //     foreach ($data->detil as $key => $value) {
                //         $this->stock_service->updateStockProduct($value);
                //     }
                //     $this->trans_sharing_service->calculateSharing($data);
                //     $res = $data;

                //     break;

                // case 'pg_dd_bri':
                //     $bind = Bind::where('id', $request->card_id)->first();
                //     $bind_before = TransPayment::where('trans_order_id', $data->id)->first();

                //     if (!$bind && $request->card_id) {
                //         return response()->json(['message' => 'Card Not Found'], 404);
                //     }
                //     if ($bind) {
                //         if (!$bind->is_valid) {
                //             return response()->json(['message' => 'Card Not Valid'], 404);
                //         }
                //     }

                //     // dd($bind_before);
                //     $payment_payload = [
                //         "sof_code" => $bind->sof_code ?? $bind_before->data['sof_code'],
                //         "bind_id" => $bind->bind_id ?? $bind_before->data['bind_id'],
                //         "refnum" => $bind->refnum ?? $bind_before->data['refnum'],
                //         "card_no" => $bind->card_no ?? $bind_before->data['card_no'],
                //         "amount" => (string) $data->sub_total,
                //         "trxid" => $data->order_id,
                //         "remarks" => $data->tenant->name ?? 'Travoy',
                //         "phone" => $bind->phone ?? $bind_before->data['phone'],
                //         "email" => $bind->email ?? $bind_before->data['email'],
                //         "customer_name" => $bind->customer_name ?? $bind_before->data['customer_name'],
                //         "bill" => (string) $data->sub_total,
                //         "fee" => (string) $data->fee,
                //         "submerchant_id" => $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id

                //     ];
                //     // log::info('Request DD inquiry => '.$payment_payload);

                //     $respon = PgJmto::inquiryDD($payment_payload);
                //     // log::info($respon);
                //     log::info('Response DD inquiry => '.$respon);

                //     if ($respon->successful()) {
                //         $res = $respon->json();
                //         if ($res['status'] == 'ERROR') {
                //             return response()->json($res, 400);
                //         }
                //         $res['responseData']['bind_id'] = $bind->bind_id;
                //         $respon = $res['responseData'];
                //         if ($data->payment === null) {
                //             $payment = new TransPayment();
                //             $payment->data = $respon;
                //             $payment->trans_order_id = $data->id;
                //             $payment->save();
                //         } else {
                //             $tans_payment = TransPayment::where('trans_order_id', $data->id)->first();
                //             $tans_payment->data = $respon;
                //             $tans_payment->save();
                //         }
                //         $data->service_fee = $respon['fee'];
                //         $data->total = $data->sub_total + $data->service_fee + $data->addon_total;
                //         $data->save();
                //         DB::commit();
                //         return response()->json($res);
                //     }
                //     return response()->json($respon->json(), 400);
                //     break;


                // case 'pg_dd_mandiri':
                //     $bind = Bind::where('id', $request->card_id)->first();
                //     $bind_before = TransPayment::where('trans_order_id', $data->id)->first();

                //     if (!$bind && $request->card_id) {
                //         return response()->json(['message' => 'Card Not Found'], 404);
                //     }
                //     if ($bind) {
                //         if (!$bind->is_valid) {
                //             return response()->json(['message' => 'Card Not Valid'], 404);
                //         }
                //     }


                //     $payment_payload = [
                //         "sof_code" => $bind->sof_code ?? $bind_before->data['sof_code'],
                //         "bind_id" => (string) ($bind?->bind_id ?? $bind_before->data['bind_id']),
                //         "refnum" => $bind->refnum ?? $bind_before->data['refnum'],
                //         "card_no" => $bind->card_no ?? $bind_before->data['card_no'],
                //         "amount" => (string) $data->sub_total,
                //         "trxid" => $data->order_id,
                //         "remarks" => $data->tenant->name ?? 'Travoy',
                //         "phone" => $bind->phone ?? $bind_before->data['phone'],
                //         "email" => $bind->email ?? $bind_before->data['email'],
                //         "customer_name" => $bind->customer_name ?? $bind_before->data['customer_name'],
                //         "bill" => (string) $data->sub_total,
                //         "fee" => (string) $data->fee,
                //         "submerchant_id" => $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id
                //     ];
                //     $respon = PgJmto::inquiryDD($payment_payload);
                //     log::info($respon);
                //     if ($respon->successful()) {
                //         $res = $respon->json();
                //         if ($res['status'] == 'ERROR') {
                //             return response()->json($res, 400);
                //         }
                //         $res['responseData']['bind_id'] = $bind->bind_id;
                //         $respon = $res['responseData'];
                //         if ($data->payment === null) {
                //             $payment = new TransPayment();
                //             $payment->data = $respon;
                //             $payment->trans_order_id = $data->id;
                //             $payment->save();
                //         } else {
                //             $tans_payment = TransPayment::where('trans_order_id', $data->id)->first();
                //             $tans_payment->data = $respon;
                //             $tans_payment->save();
                //         }
                //         $data->service_fee = $respon['fee'];
                //         $data->total = $data->sub_total + $data->service_fee + $data->addon_total;
                //         $data->save();
                //         DB::commit();
                //         return response()->json($res);
                //     }
                //     return response()->json($respon->json(), 400);
                //     break;

                default:
                    return response()->json(['error' => $payment_method->name . ' Coming Soon'], 500);

                    break;
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage(), $payment_payload], 500);
        }
    }


    public function statusPayment(Request $request, $id)
    {
        $kios = [];
        $data = TransOrder::with('payment_method')->findOrfail($id);
        try {
            DB::beginTransaction();
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE || $data->status == TransOrder::READY) {
                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
            }

            if ($data->status != TransOrder::WAITING_PAYMENT) {
                return response()->json(['status' => $data->status, 'responseData' => $data->payment ?? '']);
            }

            if (!$data->payment) {
                return response()->json(['status' => $data->status, 'responseData' => null]);
            }

            $data_payment = $data->payment->data;

            $res = PgJmto::vaStatus(
                $data_payment['sof_code'],
                $data_payment['bill_id'],
                $data_payment['va_number'],
                $data_payment['refnum'],
                $data_payment['phone'],
                $data_payment['email'],
                $data_payment['customer_name'],
                // $data_payment['submerchant_id']
                $data->sub_merchant_id

            );
            if ($res['status'] == 'success') {
                $res_data = $res['responseData'];
                $res_data['fee'] = $data_payment['fee'];
                $res_data['bill'] = $data_payment['bill'];
                $kios = [];

                if ($res_data['pay_status'] == '1') {
                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    if ($data->order_type == TransOrder::POS) {
                        $data->status = TransOrder::DONE;
                    }
                    $data->save();

                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                } else {
                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                }
                $data->payment()->update(['data' => $res_data]);
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => (string) $th], 500);
        }
    }



    public function orderList(Request $request)
    {
        $queryOrder = "CASE WHEN status = 'QUEUE' THEN 1 ";
        $queryOrder .= "WHEN status = 'WAITING_OPEN' THEN 2 ";
        $queryOrder .= "WHEN status = 'WAITING_CONFIRMATION_TENANT' THEN 3 ";
        $queryOrder .= "WHEN status = 'WAITING_CONFIRMATION_USER' THEN 4 ";
        $queryOrder .= "WHEN status = 'WAITING_PAYMENT' THEN 5 ";
        $queryOrder .= "WHEN status = 'READY' THEN 6 ";
        $queryOrder .= "WHEN status = 'PAYMENT_SUCCESS' THEN 7 ";
        $queryOrder .= "WHEN status = 'DONE' THEN 8 ";
        $queryOrder .= "WHEN status = 'CANCEL' THEN 9 ";
        $queryOrder .= "ELSE 9 END";

        $data = TransOrder::with('payment_method', 'payment', 'detil.product', 'tenant', 'casheer', 'trans_edc.bank')->when($status = request()->status, function ($q) use ($status) {
            if (is_array($status)) {
                $q->whereIn('status', $status);
            } else {
                $q->where('status', $status);
            }
        })
            ->when($start_date = $request->start_date, function ($q) use ($start_date) {
                $q->whereDate('created_at', '>=', date("Y-m-d", strtotime($start_date)));
            })
            ->when($end_date = $request->end_date, function ($q) use ($end_date) {
                $q->whereDate('created_at', '<=', date("Y-m-d", strtotime($end_date)));
            })
            ->when($statusnot = request()->statusnot, function ($q) use ($statusnot) {
                if (is_array($statusnot)) {
                    $q->whereNotIn('status', $statusnot);
                } else {
                    $q->whereNotIn('status', $statusnot);
                }
            })
            ->when($filter = request()->filter, function ($q) use ($filter) {
                return $q->where('order_id', 'like', "%$filter%");
            })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
            $q->where('tenant_id', $tenant_id);
        })->when($order_type = request()->order_type, function ($q) use ($order_type) {
            $q->where('order_type', $order_type);
        })
            ->when($customer_name = request()->customer_name, function ($q) use ($customer_name) {
                $q->where('customer_name', $customer_name)->orwhere('nomor_name', $customer_name);
            })->orderByRaw($queryOrder)->orderBy('created_at', 'desc');
        if (!request()->sort) {
            // $data = $data->orderBy('updated_at', 'desc');

        }

        if (auth()->user()->role == 'CASHIER') {
            $data = $data->where(function ($query) {
                $query->where('casheer_id', auth()->user()->id)
                    ->orWhereNull('casheer_id');
            })->get();
        } else {
            $data = $data->get();
        }

        // )->when($sort = request()->sort, function ($q) use ($sort) {
        //     if (is_array($sort)) {
        //         foreach ($sort as $val) {
        //             $jsonx = explode("&", $val);
        //             $q->orderBy($jsonx[0], $jsonx[1]);
        //         }
        //     }
        // }
        return response()->json(TrOrderResource::collection($data));
    }

    public function orderById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TrOrderResource($data));
    }

    public function paymentOrder(PaymentOrderRequest $request)
    {
        $cek_data_softdelete = TransOrder::onlyTrashed()->where('id', $request->id)->exists();
        if ($cek_data_softdelete) {
            return response()->json(['message' => 'Order has ben delete'], 422);
        }

        $data = TransOrder::findOrFail($request->id);
        if ($data->status == TransOrder::DONE || $data->status == TransOrder::CANCEL) {
            return response()->json(['message' => 'Order Status ' . $data->statusLabel()], 400);
        }

        //Cek Order detail kosong
        if (!$data->detil->count() == 0 && $data->order_type == TransOrder::ORDER_TRAVOY) {
            return response()->json(['message' => 'Order not valid, detail is Empty '], 400);
        }

        //Cek stok
        $error = [];
        foreach ($data->detil as $value) {
            if ($value->product) {
                if ($value->product->stock < $value->qty) {
                    $error['product'][] = $value->qty . ' qty order ' . $value->product->name . ' is invalid. stock available is ' . $value->product->stock;
                }

                if (!$value->product->is_active) {
                    $error['product'][] = $value->product->name . ' is not active';
                }
            } else {
                $error[]['Product '] = 'Product not available';
            }
        }

        if (count($error) > 0) {
            throw ValidationException::withMessages($error);
        }
        $payment_method = PaymentMethod::findOrFail($request->payment_method_id);
        try {
            DB::beginTransaction();
            $data->consume_type = $request->consume_type;
            $data->nomor_name = $request->nomor_name;
            switch ($payment_method->code_name) {
                case 'cash':
                    if ($data->total > $request->cash) {
                        return response()->json(['message' => "Not Enough Balance"]);
                    }
                    $payment = new TransPayment();
                    $payment->trans_order_id = $data->id;
                    $payment->data = [
                        'cash' => $request->cash,
                        'total' => $data->total,
                        'kembalian' => $request->cash - $data->total
                    ];
                    $data->payment()->save($payment);
                    $data->payment_method_id = $request->payment_method_id;
                    $data->payment_id = $payment->id;
                    $data->pay_amount = $request->cash;
                    $data->total = $data->total;
                    $data->status = TransOrder::DONE;
                    $data->save();
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    break;

                case 'tav_qr':
                    $voucher = Voucher::where('hash', request()->voucher)
                        ->where('rest_area_id', $data->tenant?->rest_area_id)
                        ->first();
                    if ($voucher == null) {
                        return response()->json(['message' => 'Scan QR dibatalkan'], 500);
                    }

                    $barrier = SaldoResource::collection($voucher?->balance_history['data']);
                    $kunci = $data->tenant->rest_area_id . '-' . $data->tenant->id;
                    $count = 0;
                    foreach ($barrier as $string) {
                        $count += substr_count($string['trx_order_id'], $kunci);
                    }
                    #barrier hut JMTO
                    if ($voucher->is_active == 0) {
                        return response()->json(['error' => 'Aktivasi QR anda di paystation'], 500);
                    }

                    ###
                    if (!$voucher) {
                        return response()->json(['message' => 'Voucher tidak ditemukan'], 500);
                    }

                    if ($voucher->balance < $data->total) {
                        return response()->json(['message' => 'Ballance tidak cukup'], 500);
                    }

                    $balance_now = $voucher->balance;
                    $voucher->balance -= $data->total;
                    $ballaceHistory = [
                        "trx_id" => $data->id,
                        "trx_order_id" => $data->order_id,
                        "trx_type" => 'Belanja',
                        "trx_area" => $data->tenant ? ($data->tenant->rest_area ? $data->tenant->rest_area->name : '') : '',
                        "trx_name" => $data->tenant ? $data->tenant->name : '',
                        "trx_amount" => $data->total,
                        "current_balance" => $voucher->balance,
                        "last_balance" => $balance_now,
                        "datetime" => Carbon::now()->toDateTimeString(),
                    ];
                    $dataHistori = $voucher->balance_history;
                    $dataHistori['data'] = array_merge([$ballaceHistory], $voucher->balance_history['data']);
                    $dataHistori['current_balance'] = $voucher->balance;
                    $voucher->balance_history = $dataHistori;
                    $voucher->qr_code_use = $voucher->qr_code_use + 1;
                    $voucher->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                    $voucher->save();

                    $payment_payload = [
                        'order_id' => $data->order_id,
                        'total' => $data->total,
                        'tenant' => $data->tenant->name ?? '',
                        'voucher' => request()->voucher
                    ];
                    $payment = new TransPayment();
                    $payment->trans_order_id = $data->id;
                    $payment->data = $payment_payload;
                    $data->payment()->save($payment);
                    $data->payment_method_id = $request->payment_method_id;
                    $data->payment_id = $payment->id;
                    $data->total = $data->total + $data->service_fee;
                    $data->saldo_qr = $voucher->balance;
                    $data->status = TransOrder::DONE;

                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }

                    $this->trans_sharing_service->calculateSharing($data);


                    $trans_saldo = TransSaldo::with('trans_invoice')->ByTenant()->first();
                    if (!$trans_saldo) {
                        $trans_saldo = TransSaldo::create([
                            'rest_area_id' => $data->rest_area_id,
                            'tenant_id' => auth()->user()->tenant_id,
                            'saldo' => $data->total,
                            'created_at' => Carbon::now(),
                        ]);
                    } else {
                        $trans_saldo->saldo += $data->total;
                        $trans_saldo->save();
                    }

                    $data->save();
                    break;

                case 'edc':
                    $data->payment_method_id = $request->payment_method_id;
                    $edc = new TransEdc();
                    $edc->trans_order_id = $data->id;
                    $edc->bank_id = $request->bank_id;
                    $edc->card_nomor = $request->card_nomor;
                    $edc->ref_nomor = $request->ref_nomor;
                    $data->trans_edc()->save($edc);
                    $data->status = TransOrder::DONE;
                    $data->save();
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    break;

                default:
                    return response()->json(['message' => $payment_method->name . ' Coming Soon'], 500);
                    break;
            }
            DB::commit();
            return response()->json(new TrOrderResource($data));
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function changeStatusOrder($id, ChangeStatusOrderReqeust $request)
    {
        $data = TransOrder::findOrFail($id);

        if ($data->status == TransOrder::DONE || $data->status == TransOrder::CANCEL) {
            return response()->json(['message' => 'Order status ' . $data->statusLabel()], 400);
        }

        $data->status = $request->status;
        $data->code_verif = random_int(1000, 9999);
        if ($request->status == TransOrder::CANCEL) {
            $data->canceled_by = TransOrder::CANCELED_BY_CASHEER;
            $data->canceled_name = auth()->user()->name;
            $data->reason_cancel = $request->reason_cancel;
        }

        $data->save();

        return response()->json($data);
    }

    public function settlement(Request $request)
    {
        $tanggal = null;
        if ($request->request) {
            $tanggal = Carbon::parse($request->tanggal);
        }
        TransOrder::fromTravoy()
            ->when($tanggal, function ($q) use ($tanggal) {
                $q->whereYear('created_at', '=', $tanggal->format('y'))
                    ->whereMonth('created_at', '=', $tanggal->format('m'))
                    ->get();
            });
    }


    public function manualArsip($id, Request $request)
    {
        $data = TransOrder::fromTravoy()->findOrfail($id);

        $result = DB::transaction(function () use ($request, $data) {
            $arsip = $data->toArray();
            $explode = explode('-', $data->order_id);
            $data->order_id = $explode[0] . '-' . $request->phone . '-' . $request->number . '-' . $explode[3];
            $data->save();
            $data->trans_order_arsip()->create($arsip);
            return $data;
        });

        return response()->json($result);
    }

    public function logArsip($id)
    {
        $data = TransOrderArsip::where('trans_order_id', $id)->get();
        return response()->json($data);
    }
}