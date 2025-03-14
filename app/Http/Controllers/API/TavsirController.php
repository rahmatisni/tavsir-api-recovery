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
use App\Models\Supertenant;
use App\Models\TransDerek;
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
use App\Http\Resources\Tavsir\TrOrderResourceDerek;
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
use App\Models\LaJmto;
use App\Models\CallbackLA;
use App\Models\PgJmtoSnap;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TenantLa;
use App\Models\TransEdc;
use App\Models\TransOrderArsip;
use App\Models\TransPayment;
use App\Models\TransSaldo;
use App\Models\TransSharing;
use App\Models\Sharing;
use App\Models\TransStock;
use App\Models\User;
use App\Models\Voucher;
use App\Services\External\KiosBankService;
use App\Services\External\TravoyService;
use App\Services\Payment\PaymentService;
use App\Services\StockServices;
use App\Services\TransSharingServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToArray;

class TavsirController extends Controller
{
    public function __construct(
        protected StockServices $stock_service,
        protected TransSharingServices $trans_sharing_service,
        protected PaymentService $servicePayment,
        protected KiosBankService $serviceKiosBank,
        protected TravoyService $travoyService,
    ) {
    }

    public function tenantSupertenantList(Request $request)
    {
        $identifier = auth()->user()?->tenant;
        $data = Supertenant::where('id', $identifier?->supertenant_id)->when($identifier, function ($q) use ($identifier) {
            if ($identifier?->is_supertenant != NULL) {
                return $q->with('tenant');
            } else {
                return $q->with([
                    'tenant' => function ($query) use ($identifier) {

                        $query->where('ref_tenant.id', '=', $identifier->id);
                    }
                ]);
            }
        })
            ->firstOrFail();
        return response()->json($data);
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

        $active = [];
        $inactive = [];
        foreach ($data as $value) {
            $cek_product_have_not_active = $value->trans_product_raw->where('is_active', 0)->count();
            $stock = $value->stock;
            // $value->stock_sort = $stock > 0 ? 0:1;
            $value->stock_sort = $value->stock === 0 ? 1 : ($value->is_active === 0 ? 1 : 0);
            if ($value->is_composit === 1 && $value->is_active === 1) {
                if ($cek_product_have_not_active > 0) {
                    $value->stock_sort = 1;
                } else {
                    $liststock = [];
                    foreach ($value->trans_product_raw as $item) {
                        $liststock[] = floor($item->stock / $item->pivot->qty);
                    }
                    $temp_stock = count($liststock) == 0 ? 0 : min($liststock);

                    $value->stock_sort = $temp_stock > 0 ? 0 : 1;
                }
            }
            if ($value->stock_sort == 0) {
                $active[] = $value;
            } else {
                $inactive[] = $value;
            }
        }
        $sortedArray = array_merge($active, $inactive);
        // return response()->json($sortedArray);

        return response()->json(ProductSupertenantResource::collection($sortedArray));

        // return response()->json(ProductSupertenantResource::collection($data));
    }

    public function orderSuperTenant(TrOrderRequest $request)
    {
        try {
            $data = TransOrder::find($request->id);

            DB::beginTransaction();
            if (!$data) {
                $data = new TransOrder();
                $data->order_type = TransOrder::POS;
                $data->order_id = (auth()->user()->tenant?->rest_area_id ?? '0') . '-' . (auth()->user()->tenant->supertenant_id ?? '0') . '-STAV-' . date('YmdHis');
                $data->status = TransOrder::CART;
            }
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE) {
                return response()->json(['message' => 'Order status ' . $data->statusLabel()], 400);
            }
            $data->rest_area_id = auth()->user()->tenant?->rest_area_id ?? null;
            $data->supertenant_id = auth()->user()->tenant->id;
            $data->tenant_id = auth()->user()->tenant_id;
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
            $data->total = $data->sub_total + $data->fee + $data->service_fee;


            $now = Carbon::now()->format('Y-m-d H:i:s');
            $sharing = Sharing::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['sedang_berjalan', 'belum_berjalan'])
                ->where('waktu_mulai', '<=', $now)
                ->where('waktu_selesai', '>=', $now)->first();
            if ($sharing?->sharing_config) {
                $nilai_sharing = json_decode($sharing->sharing_config);
                foreach ($nilai_sharing as $value) {
                    $harga = (int) ($data->sub_total) + (int) ($data->addon_total);
                    $sharing_amount_unround = (($value / 100) * $harga);
                    // $sharing_amount[] = ($value/100).'|'.$harga.'|'.$sharing_amount_unround;
                    $sharing_amount[] = $sharing_amount_unround;
                }
                $data->sharing_code = $sharing->sharing_code ?? null;
                $data->sharing_amount = $sharing_amount ?? null;
                $data->sharing_proportion = $sharing->sharing_config ?? null;
            } else {
                $data->sharing_code = [(string) $data->tenant_id];
                $data->sharing_proportion = [100];
                $data->sharing_amount = [$data->sub_total + (int) ($data->addon_total)];
            }


            $data->save();
            $data->detil()->saveMany($order_detil_many);

            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
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

    public function orderListMemberOfSupertenant(Request $request)
    {
        $tenant_user = auth()->user()->tenant;
        $data = TransOrder::with('detil.product.tenant')
            ->whereIn('status', [TransOrder::DONE, TransOrder::REFUND])
            ->where('supertenant_id', $tenant_user->supertenant_id ?? 0)
            ->when($payment_method = request()->payment_method, function ($q) use ($payment_method) {
                $q->where('payment_method_id', $payment_method);
            })
            ->when($status = request()->status, function ($q) use ($status) {
                if (is_array($status)) {
                    $q->whereIn('status', $status)->orwhereIn('status', json_decode($status[0]) ?? []);
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
            })
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
            ->whereIn('status', [TransOrder::REFUND, TransOrder::PAYMENT_SUCCESS, TransOrder::DONE])
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

    public function orderRefund(Request $request, $id)
    {
        $data = TransOrder::byRole()->findOrfail($id);
        if ($data->is_refund) {
            return response()->json([
                'message' => 'Order sudah di refund'
            ], 400);
        }

        $user = auth()->user();
        $tenant_id = $user->tenant_id;
        $tenant = User::where('tenant_id', $tenant_id)->where('role', User::TENANT)->get();

        if (!Hash::check($request->pin_casheer, $user->pin)) {
            return response()->json(['message' => 'PIN Kasir salah'], 400);
        }
        $validator = 0;
        foreach ($tenant as $x) {
            if (Hash::check($request->pin_tenant, $x->password)) {
                $validator = 1;
            }
        }
        if ($validator == 0) {
            return response()->json(['message' => 'Password Tenant salah'], 400);
        }

        if ($data->payment_method_id == 4) {
            $parts = explode("-", $data->order_id);
            $lastElement = end($parts);
            $originalID = $data->payment->payment['la_response']['data']['trxId'];

            $payload = [
                "commandID" => "ReverseTransaction",
                "originatorConversationID" => $lastElement,
                "originalReceiptNumber" => $originalID,
                "trxAmount" => $data->total,
                "merchantTrxID" => $lastElement
            ];
            $refund_la = LaJmto::qrRefund($payload);
            if ($refund_la['status'] == 'success') {
            } else {
                return response()->json([
                    'message' => 'Refund sebesar ' . $data->total . ' gagal!',
                    'data' => $refund_la
                ], 422);
            }
        }

        $total_refund = 0;
        $order_refund = $data->detil;

        foreach ($order_refund as $v) {
            $total_refund += $v->total_price;
        }
        $data->is_refund = 1;
        $data->status = TransOrder::REFUND;
        $data->save();
        $data->payment->data = [
            'cash' => $data->pay_amount,
            'total' => $data->total,
            'kembalian' => $data->pay_amount - $data->total,
            'jumlah_refund' => $data->total
        ];
        $data->payment->save();

        return response()->json([
            'message' => 'Refund sebesar ' . $total_refund,
            'data' =>
                new TrOrderResource($data)

        ]);
    }

    public function productList(Request $request)
    {

        // $super_tenant_id = ((auth()->user()->role === 'TENANT' && auth()->user()->tenant_id == request()->tenant_id) ? auth()->user()->supertenant_id : NULL);

        // super tenant
        if ((auth()->user()->tenant->is_supertenant != NULL || auth()->user()->tenant->is_supertenant != 0) && auth()->user()->role === 'CASHIER') {
            $result = $this->productSupertenantList($request);
            return $result;
        }


        $data = Product::byTenant()->byType(ProductType::PRODUCT)->with('tenant')->with('trans_product_raw')->when($filter = $request->filter, function ($q) use ($filter) {
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
        $data = $data->orderBy('name', 'asc')->get();

        $active = [];
        $inactive = [];
        foreach ($data as $value) {
            $cek_product_have_not_active = $value->trans_product_raw->where('is_active', 0)->count();
            $stock = $value->stock;
            // $value->stock_sort = $stock > 0 ? 0:1;
            $value->stock_sort = $value->stock === 0 ? 1 : ($value->is_active === 0 ? 1 : 0);
            if ($value->is_composit === 1 && $value->is_active === 1) {
                if ($cek_product_have_not_active > 0) {
                    $value->stock_sort = 1;
                } else {
                    $liststock = [];
                    foreach ($value->trans_product_raw as $item) {
                        $liststock[] = floor($item->stock / $item->pivot->qty);
                    }
                    $temp_stock = count($liststock) == 0 ? 0 : min($liststock);

                    $value->stock_sort = $temp_stock > 0 ? 0 : 1;
                }
            }
            if ($value->stock_sort == 0) {
                $active[] = $value;
            } else {
                $inactive[] = $value;
            }
        }
        $sortedArray = array_merge($active, $inactive);
        // return response()->json($sortedArray);

        return response()->json(TrProductResource::collection($sortedArray));
    }

    public function productStore(TavsirProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new Product();
            $data->tenant_id = auth()->user()->tenant_id;
            $data->fill($request->all());
            $data->type = ProductType::PRODUCT;
            $data->sav();
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
        $super_tenant_state = auth()->user()->tenant->is_supertenant;
        $super_tenant_id = auth()->user()->tenant->supertenant_id;

        if ($super_tenant_state > 0 && auth()->user()->role === 'CASHIER') {
            $arr_tenant = Tenant::where('supertenant_id', auth()->user()->tenant->id)->orWhere('id', auth()->user()->tenant->id)->pluck('id')->toArray();
            $data = Category::with('tenant')->byType(ProductType::PRODUCT)->when($filter = $request->filter, function ($q) use ($filter) {
                return $q->where('name', 'like', "%$filter%");
            })->when(auth()->user()->tenant->is_supertenant != NULL, function ($q) use ($arr_tenant) {
                return $q->whereIn('tenant_id', $arr_tenant);
            })
                ->orderBy('name')
                ->get()
                ->sortBy('tenant.name', SORT_REGULAR, false);

            return response()->json(TrCategoryResource::collection($data));
        }
        $data = Category::byType(ProductType::PRODUCT)
            ->byTenant()->when($filter = $request->filter, function ($q) use ($filter) {
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

            // super tenant
            if (auth()->user()->tenant->is_supertenant === 1) {
                $result = $this->orderSuperTenant($request);
                return $result;
            }
            /////
            $data = TransOrder::find($request->id);

            DB::beginTransaction();
            if (!$data) {
                $data = new TransOrder();
                $data->order_type = TransOrder::POS;
                $data->order_id = (auth()->user()->tenant->rest_area_id ?? '0') . '-' . (auth()->user()->tenant_id ?? '0') . '-POS-' . date('YmdHis') . rand(0, 100);
                $data->status = TransOrder::CART;
                $data->nomor_name = $request->nomor_name;
            }
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE) {
                return response()->json(['message' => 'Order status ' . $data->statusLabel()], 400);
            }
            // $data->sharing_code = $tenant->sharing_code ?? null;
            $data->rest_area_id = auth()->user()->tenant->rest_area_id ?? null;
            $data->tenant_id = auth()->user()->tenant_id;
            $data->business_id = auth()->user()->business_id;
            $data->casheer_id = auth()->user()->id;
            $data->nomor_name = $request->nomor_name;

            $data->detil()->delete();
            $order_detil_many = [];
            $data->save();

            $sub_total = 0;
            $margin = 0;
            foreach ($request->product as $k => $v) {
                $product = Product::byType(ProductType::PRODUCT)->find($v['product_id']);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->price_capital = $product->price_capital;
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
                $raw_margin = $order_detil->price_capital * $v['qty'];
                $order_detil->note = $v['note'];

                $sub_total += $order_detil->total_price;
                $margin += $raw_margin;

                $order_detil_many[] = $order_detil;
            }

            $extra_price = ExtraPrice::byTenant($data->tenant_id)->aktif()->get();
            $data->margin = $sub_total - $margin;
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

            // ======OLD======
            // $tenant = Tenant::where('id', auth()->user()->tenant_id)->first();
            // if ($tenant->sharing_config) {
            //     $tenant_sharing = json_decode($tenant->sharing_config);
            //     foreach ($tenant_sharing as $value) {
            //         $harga = (int) ($data->sub_total) + (int) ($data->addon_total);
            //         $sharing_amount_unround = (($value / 100) * $harga);
            //         // $sharing_amount[] = ($value/100).'|'.$harga.'|'.$sharing_amount_unround;
            //         $sharing_amount[] = $sharing_amount_unround;
            //     }
            //     $data->sharing_code = $tenant->sharing_code ?? null;
            //     $data->sharing_amount = $sharing_amount ?? null;
            //     $data->sharing_proportion = $tenant->sharing_config ?? null;
            // }

            // ======OLD======
            $now = Carbon::now()->format('Y-m-d H:i:s');
            $sharing = Sharing::where('tenant_id', auth()->user()->tenant_id)->whereIn('status', ['sedang_berjalan', 'belum_berjalan'])
                ->where('waktu_mulai', '<=', $now)
                ->where('waktu_selesai', '>=', $now)->first();
            if ($sharing?->sharing_config) {
                $nilai_sharing = json_decode($sharing->sharing_config);
                foreach ($nilai_sharing as $value) {
                    $harga = (int) ($data->sub_total) + (int) ($data->addon_total);
                    $sharing_amount_unround = (($value / 100) * $harga);
                    // $sharing_amount[] = ($value/100).'|'.$harga.'|'.$sharing_amount_unround;
                    $sharing_amount[] = $sharing_amount_unround;
                }
                $data->sharing_code = $sharing->sharing_code ?? null;
                $data->sharing_amount = $sharing_amount ?? null;
                $data->sharing_proportion = $sharing->sharing_config ?? null;
            } else {
                $data->sharing_code = [(string) $data->tenant_id];
                $data->sharing_proportion = [100];
                $data->sharing_amount = [$data->sub_total + (int) ($data->addon_total)];
            }
            $data->save();
            $data->detil()->saveMany($order_detil_many);
            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
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
            ->whereIn('status', [TransOrder::CART, TransOrder::WAITING_PAYMENT])
            ->count();
        // ->get();

        return response()->json(['count' => $data]);
    }

    public function cartDelete(Request $request)
    {
        $data = TransOrder::whereIn('id', $request->id)
            ->where('tenant_id', '=', auth()->user()->tenant_id)
            ->where('order_type', '=', TransOrder::POS)
            // ->where('status', '=', TransOrder::CART)
            ->whereIn('status', [TransOrder::CART, TransOrder::WAITING_PAYMENT])
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

    public function paymentMethod(Request $request)
    {
        $paymentMethods = PaymentMethod::all();
        $self_order = [4, 5, 7, 9, 11];
        $travshop = [5, 6, 7, 8, 9, 11];
        $tavsir = [1, 2, 3, 4, 10];

        if ($request->trans_order_id) {
            $trans_order = TransOrder::with('tenant')->findOrfail($request->trans_order_id);
            $param_removes = Tenant::where('id', $trans_order->tenant_id)->first();
            if ($param_removes == null && $trans_order->order_type == 'ORDER_TRAVOY') {
                $self_order = [];
                $travshop = [5, 6, 7, 8, 9, 11];
                $tavsir = [];
            } else {
                $intersect = json_decode($param_removes->list_payment);
                if ($param_removes->list_payment == null) {
                    $self_order = [];
                    $travshop = [];
                    $tavsir = [1, 2];
                } else {
                    $self_order = array_intersect($self_order, $intersect);
                    $travshop = array_intersect($travshop, $intersect);
                    $tavsir = array_intersect($tavsir, $intersect);
                }
            }

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

                if ($value?->sof_id) {

                    if ($value?->sof_id == null || $tenant_is_verified == null) {
                        $value->percentage = null;
                        $value->fee = null;
                    } else {
                        $data = PgJmto::tarifFee($value->sof_id, $value->payment_method_id, $value->sub_merchant_id, $trans_order->sub_total);
                        Log::warning($data);
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
            }
        }


        return response()->json($paymentMethods);
    }

    public function createPayment(TsCreatePaymentRequest $request, $id)
    {
        $payment_payload = [];
        $data = TransOrder::with('tenant')->findOrfail($id);
        try {

            if (request()->order_from_qr == true) {
                if ($data->status == TransOrder::CART || $data->status == TransOrder::PENDING || $data->status == null) {
                    $data->status = TransOrder::WAITING_PAYMENT;
                    $data->customer_id = $request->customer_id;
                }
            }
            if ($data->status == TransOrder::QUEUE) {
                $data->status = TransOrder::WAITING_PAYMENT;
            }
            if (($data->order_type == TransOrder::POS) && ($data->order_type == TransOrder::CART)) {
                $data->status = TransOrder::WAITING_PAYMENT;
            }

            // command ini
            // if ($data->status != TransOrder::WAITING_PAYMENT) {
            //     return response()->json(['info' => $data->status], 422);
            // }

            //Cek deposit
            if ($data->order_type == TransOrder::ORDER_TRAVOY) {
                $cekProduct = ProductKiosBank::where('kode', $data->codeProductKiosbank())->first();
                //Skip jika jatelindo
                if ($cekProduct?->integrator != 'JATELINDO') {
                    $deposit = $this->serviceKiosBank->cekDeposit();
                    if ($deposit['rc'] == '00') {
                        if ((int) $deposit['deposit'] < $data->sub_total) {
                            return response()->json(['info' => 'Deposit ' . $deposit['deposit'] . ' < ' . $data->sub_total], 422);
                        }
                    } else {
                        return response()->json(['info' => 'Deposit ', 'data' => $deposit], 422);
                    }
                }
            }


            $data->payment_method_id = $request->payment_method_id;
            $payment_method = PaymentMethod::find($request->payment_method_id);
            if ($data->order_type != TransOrder::ORDER_TRAVOY) {
                $data->total = $data->sub_total + $data->addon_total + $data->fee;

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
                    return response()->json($error, 422);
                    // throw ValidationException::withMessages($error);
                }
            }
            DB::beginTransaction();
            $additional_data = [
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                ...$request->all()
            ];
            //SERVICE PAYMENT CRETE
            $paymentResult = $this->servicePayment->create($payment_method, $data, $additional_data);
            $payment_payload = $paymentResult->data;

            if ($paymentResult->status == false) {
                return response()->json($paymentResult, 422);
            }
            $data->service_fee = $paymentResult->data['responseData']['fee'];
            // $data->service_fee = $paymentResult->fee;
            $data->total = $data->total + $data->service_fee;
            $data->sub_merchant_id = $data->tenant?->sub_merchant_id ?? $data->sub_merchant_id;
            $data->save();
            DB::commit();
            $response = $paymentResult->data;
            // unset($response['responseSnap']);
            return response()->json($response);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage(), $payment_payload], 500);
        }
    }

    public function statusPayment(Request $request, $id)
    {
        $data = TransOrder::with('payment_method', 'payment')->findOrfail($id);

        try {
            DB::beginTransaction();
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE || $data->status == TransOrder::READY) {
                $kios = [];
                if ($data->order_type == TransOrder::ORDER_TRAVOY && $data->status != TransOrder::DONE) {

                    if ($data->productKiosbank()->integrator == 'JATELINDO') {
                        return response()->json(['token' => $data->log_kiosbank->data['bit62'] ?? '']);
                    }

                    $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                    $adminBank = $datalog['data']['data']['adminBank'] ?? '000000000000';
                    $refid = $datalog['data']['referenceID'];

                    $kios = $this->serviceKiosBank->cekStatus($data->sub_total, $data->order_id, $adminBank, $data->harga_kios);
                    $kios['data']['harga_kios'] = $data->harga_kios;
                    $kios['data']['harga'] = $data->sub_total ?? '0';
                    $kios['description'] = $kios['description'] ?? $kios['data']['status'] ?? $kios['data']['description'] ?? '-';
                    if ($kios['rc'] == '00' || $kios['rc'] == "00" || $kios['rc'] == 00) {
                        if (str_contains($kios['description'] ?? $kios['data']['status'], 'BERHASIL')) {
                            $data->status = TransOrder::DONE;
                        }
                        if (str_contains($kios['description'] ?? $kios['data']['status'], 'SUKSES')) {
                            $data->status = TransOrder::DONE;
                        } else {
                            // log::info($kios['description'].' casenya masuk sini');
                            $data->status = TransOrder::PAYMENT_SUCCESS;
                        }
                    }
                    // $rc_coll = array('2', '10', '12', '15', '17', '18', '27', '34', '37', '40', '41', '42', '46', '60', '62', '64', '65', '68', '69', '70', '72', '73', '74', '75', '78', '79', '80', '83', '85', '86');
                    $rc_coll = array('x');

                    if (in_array($kios['rc'], $rc_coll)) {

                        //inquiry ulang
                        $ref = explode('-', $data->order_id);
                        $random_id = rand(100000000000, 999999999999);
                        $data->order_id = $ref[0] . '-' . $ref[1] . '-' . $random_id . '-' . Carbon::now()->timestamp;
                        Log::info('REPROCESS --> BEFORE => ' . $ref[2] . 'AFTER => ' . $random_id);

                        $productId = $ref[0];
                        $customerID = $ref[1];
                        $referenceID = (string) $random_id;
                        $data->save();
                        DB::commit();


                        if ($data->description == 'single') {
                            $kios = $this->serviceKiosBank->singlePayment($data->sub_total, $data->order_id, $data->harga_kios);
                            Log::info(['bayar susulan => ', $kios]);
                        }

                        if ($data->description == 'dual') {
                            $res_json = $this->serviceKiosBank->reinquiry($productId, $customerID, $referenceID);
                            $res_json = $res_json->json();
                        }

                        if ($data->description == 'dual' && $res_json['rc'] == '00') {
                            if ($res_json['productID'] == '520021' || $res_json['productID'] == '520011') {
                                $data->harga_kios = $res_json['data']['total'];

                                //harga jual

                                $harga_jual_kios = ProductKiosBank::where('kode', $res_json['productID'])->first() ?? $res_json['data']['total'];
                                $data->sub_total = ($harga_jual_kios?->harga ?? 0) + $res_json['data']['total'];
                                $data->total = $data->sub_total + $data->fee;
                                $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                                $res_json['data']['harga'] = $data->sub_total;
                                $res_json['description'] = 'INQUIRY';

                                $data->save();
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                    'data' => $res_json
                                ]);
                            } else {
                                $data->harga_kios = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'] ?? $data->harga_kios;
                                //harga jual
                                $harga_jual_kios = ProductKiosBank::where('kode', $ref[0])->first();
                                // $order->sub_total = $harga_jual_kios?->harga ?? $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                                $data->total = $data->sub_total + $data->fee;
                                $res_json['data']['harga_kios'] = $data->harga_kios;
                                // $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                                $res_json['data']['harga'] = $data->sub_total;
                                $res_json['description'] = 'INQUIRY';
                                $res_json['data']['adminBank'] = $res_json['data']['adminBank'] ?? $res_json['data']['AB'] ?? '000000000000';

                                $data->save();
                                Log::info($data);
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                    'data' => $res_json
                                ]);
                            }

                            //pay ulang
                            // if ($data->description == 'single') {
                            //     $kios = $this->serviceKiosBank->singlePayment($data->sub_total, $data->order_id, $data->harga_kios);
                            //     Log::info(['bayar susulan => ', $kios]);

                            // }
                            if ($data->description == 'dual') {
                                $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                                $tagihan = $datalog['data']['data']['tagihan'] ?? $datalog['data']['data']['harga_kios'];
                                $admin = $datalog['data']['data']['adminBank'] ?? '000000000000';
                                $total = $datalog['data']['data']['total'] ?? $datalog['data']['data']['harga_kios'] ?? $tagihan;
                                $kios = $this->serviceKiosBank->dualPayment($data->sub_total, $data->order_id, $tagihan, $admin, $total);
                                Log::info(['bayar susulan => ', $kios]);
                            }
                            $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                            $kios['data']['harga'] = $kios['data']['harga'] ?? ($data->sub_total ?? '0');
                            // $kios['data']['nama'] = $kios['data']['nama'] ?? $datalog['data']['data']['nama'] ?? '-';
                            // $kios['data']['nominalProduk'] = $kios['data']['nominalProduk'] ?? $datalog['data']['data']['nominalProduk'] ?? '0';
                            $kios['description'] = $kios['description'] ?? $kios['data']['status'] ?? $kios['data']['description'] ?? '';
                            $kios['data']['harga_kios'] = $data->harga_kios;
                            $kios['data']['harga'] = $data->sub_total ?? '0';

                            if ($kios['rc'] == '00' || $kios['rc'] == "00" || $kios['rc'] == 00) {
                                if (str_contains($kios['description'] ?? $kios['data']['status'], 'BERHASIL')) {
                                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                        'data' => $kios,
                                        'payment' => $kios,

                                    ]);
                                    $data->status = TransOrder::DONE;
                                    $data->save();
                                    DB::commit();
                                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                                }
                                if (str_contains($kios['description'] ?? $kios['data']['status'], 'SUKSES')) {
                                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                        'data' => $kios,
                                        'payment' => $kios,

                                    ]);
                                    $data->status = TransOrder::DONE;
                                    $data->save();
                                    DB::commit();
                                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                                } else {
                                    $kios['description'] = $kios['description'] ?? $kios['data']['description'];
                                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                        'data' => $kios,
                                        'payment' => $kios,

                                    ]);
                                    $data->status = TransOrder::READY;
                                    $data->save();
                                    DB::commit();
                                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                                }
                            } else {
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                                    'data' => $kios,
                                    'payment' => $kios,

                                ]);
                                $data->status = TransOrder::PAYMENT_SUCCESS;
                                $data->save();
                                DB::commit();
                                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                            }
                            //end pay ulang
                        }
                        // end inquiry ulang
                    }

                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                        'data' => $kios
                    ]);
                    $data->save();
                    DB::commit();
                }
                if ($data->order_type == TransOrder::ORDER_DEREK_ONLINE) {
                    $travoy = $this->travoyService->detailDerek($id, $request->id_user, $request->token);
                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
                }
                if ($data->payment_method_id == '4') {
                }
                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
            }

            if ($data->status != TransOrder::WAITING_PAYMENT) {
                return response()->json(['status' => $data->status, 'responseData' => $data->payment ?? '']);
            }

            if (!$data->payment) {
                return response()->json(['status' => $data->status, 'responseData' => null]);
            }

            $payment_result = $this->servicePayment->statusOrder($data, $request->all());

            if ($payment_result->status != true) {
                return response()->json([
                    ...$payment_result->data,
                    'status' => $data->status
                ], 422);
            }
            $data->save();
            DB::commit();
            return response()->json($payment_result->data);
            //END REFACTOR
            //CODE DIBWAH INI TIDAK DI EKSEUSI, SEMENTARA UNTUK KOMPARE DATA DENGAN REFACTOR
            $data_payment = $data->payment->data;
            if ($data->payment_method->code_name == 'pg_dd_bri') {
                $payload = $data_payment;
                $payload['submerchant_id'] = $data->sub_merchant_id;
                $payload['payrefnum'] = $data_payment['refnum'];
                $res = PgJmto::statusDD($payload);
                // $res = PgJmto::paymentDD($payload);

                if ($res->successful()) {
                    $res = $res->json();

                    if ($res['status'] == 'ERROR') {
                        return response()->json([
                            "message" => "ERROR!",
                            "errors" => [
                                $res
                            ]
                        ], 422);
                    }
                    $is_dd_pg_success = $res['responseData']['pay_refnum'] ?? null;
                    if ($is_dd_pg_success == null) {
                        return response()->json([
                            "message" => "ERROR!",
                            "errors" => [
                                $res
                            ]
                        ], 422);
                    }

                    $respon = $res['responseData'];
                    if ($data->payment === null) {
                        $payment = new TransPayment();
                        $payment->payment = $respon;
                        $payment->data = $respon;
                        $data->payment()->save($payment);
                    } else {
                        $pay = TransPayment::where('trans_order_id', $data->id)->first();
                        $pay->data = $respon;
                        $pay->save();
                    }

                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    if ($data->order_type === TransOrder::ORDER_TRAVOY) {
                        return $this->payKios($data, $id);
                    }
                    if ($data->order_type == TransOrder::POS) {
                        $data->status = TransOrder::DONE;
                    }

                    $data->save();
                    //Cek Payment kios
                    if ($data->order_type === TransOrder::ORDER_DEREK_ONLINE) {
                        $data->save();
                        DB::commit();

                        $travoy = $this->travoyService->detailDerek($id, $request->id_user, $request->token);
                        return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
                    }

                    //End payment kios
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    DB::commit();
                    return response()->json($data);
                }

                return response()->json($res->json(), 400);
            }

            if ($data->payment_method->code_name == 'pg_dd_mandiri') {
                if (!$request->otp) {
                    return response()->json([
                        "message" => "The given data was invalid.",
                        "errors" => [
                            "otp" => [
                                "The otp field is required."
                            ]
                        ]
                    ], 422);
                }
                $payload = $data_payment;
                $payload['otp'] = $request->otp;
                $payload['submerchant_id'] = $data->sub_merchant_id;
                $res = PgJmto::paymentDD($payload);
                if ($res->successful()) {
                    $res = $res->json();

                    if ($res['status'] == 'ERROR') {
                        return response()->json([
                            "message" => "ERROR!",
                            "errors" => [
                                $res
                            ]
                        ], 422);
                    }
                    $res['responseData']['card_id'] = $payload['card_id'] ?? '';
                    $respon = $res['responseData'];
                    if ($data->payment === null) {
                        $payment = new TransPayment();
                        $payment->payment = $respon;
                        $payment->data = $respon;
                        $data->payment()->save($payment);
                    } else {
                        $pay = TransPayment::where('trans_order_id', $data->id)->first();
                        $pay->data = $respon;
                        $pay->save();
                    }

                    $data->status = TransOrder::PAYMENT_SUCCESS;

                    if ($data->order_type === TransOrder::ORDER_TRAVOY) {
                        return $this->payKios($data, $id);
                    }
                    if ($data->order_type == TransOrder::POS) {
                        $data->status = TransOrder::DONE;
                    }

                    $data->save();
                    //Cek Payment kios
                    if ($data->order_type === TransOrder::ORDER_DEREK_ONLINE) {
                        $data->save();
                        DB::commit();

                        $travoy = $this->travoyService->detailDerek($id, $request->id_user, $request->token);
                        return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
                    }

                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    DB::commit();
                    return $data;
                }
                return response()->json($res->json(), 400);
            }

            if ($data->payment_method->code_name == 'pg_link_aja') {

                // $userAgent = $request->header('User-Agent');

                // Check the User-Agent header
                // if (strpos($userAgent, 'PostmanRuntime/7.34.0') !== false) {
                //     return response()->json(['status' => 'ERROR', 'payment_info' => 'Illegal Host Access!'],422);
                // }  
                $data_la = TenantLa::where('tenant_id', $data->tenant_id)->firstOrFail();
                $res = LAJmto::qrStatus(
                    $data_payment['bill_id'],
                    $data_la
                );

                if (isset($res['status']) && $res['status'] == 'success') {
                    $res_data = $res['responseData'];
                    $res_data['fee'] = $data_payment['fee'];
                    $kios = [];
                    if ($res_data['pay_status'] === '1') {
                        if ($data->status === TransOrder::WAITING_PAYMENT) {
                            $data->status = TransOrder::PAYMENT_SUCCESS;
                            $data->save();
                            if ($data->order_type === TransOrder::ORDER_TRAVOY) {
                                return $this->payKios($data, $id);
                            }
                        }
                        if ($data->order_type === TransOrder::POS) {
                            $data->status = TransOrder::DONE;
                        }
                        if ($data->order_type === TransOrder::ORDER_SELF_ORDER || TransOrder::ORDER_TAKE_N_GO) {
                            $fcm_token = User::where([['id', $data->casheer_id]])->get();
                            $ids = array();
                            foreach ($fcm_token as $val) {
                                if ($val['fcm_token'] != null && $val['fcm_token'] != '')
                                    array_push($ids, $val['fcm_token']);
                            }
                            if ($ids != '') {
                                $payload = array(
                                    'id' => $data->id,
                                    'type' => 'click',
                                    'action' => 'payment_success'
                                );
                                $result = sendNotif($ids, '💰Pesanan Telah Dibayar', 'Yuk segera siapkan pesanan atas transaksi ' . $data->order_id, $payload);
                            }
                        }
                        $data->save();
                        if ($data->order_type === TransOrder::ORDER_DEREK_ONLINE) {
                            $data->status = TransOrder::PAYMENT_SUCCESS;
                            $data->save();
                            DB::commit();

                            $travoy = $this->travoyService->detailDerek($id, $request->id_user, $request->token);
                            return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
                        }
                        foreach ($data->detil as $key => $value) {
                            $this->stock_service->updateStockProduct($value);
                        }
                        $pay = TransPayment::where('trans_order_id', $data->id)->first();
                        $pay->data = $res_data;
                        $pay->payment = $res;
                        $pay->orderid_sof = $res['responseData']['merchantTrxID'];
                        $pay->save();
                    } else {
                        return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios, 'payment_info' => $res['la_response']['message']]);
                    }
                    $data->payment()->update(['data' => $res_data]);
                } else {
                    return response()->json($res, 500);
                }
                DB::commit();
                return response()->json($res);
            }
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
                if ($res_data['pay_status'] === '1') {
                    if ($data->status === TransOrder::WAITING_PAYMENT) {
                        $data->status = TransOrder::PAYMENT_SUCCESS;
                        $data->save();
                        if ($data->order_type === TransOrder::ORDER_TRAVOY) {
                            return $this->payKios($data, $id);
                        }
                    }
                    if ($data->order_type === TransOrder::POS) {
                        $data->status = TransOrder::DONE;
                    }
                    $data->save();
                    if ($data->order_type === TransOrder::ORDER_DEREK_ONLINE) {
                        $data->status = TransOrder::PAYMENT_SUCCESS;
                        $data->save();
                        DB::commit();

                        $travoy = $this->travoyService->detailDerek($id, $request->id_user, $request->token);
                        return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
                    }
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                } else {
                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                }
                $data->payment()->update(['data' => $res_data, 'payment' => $res_data]);
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => (string) $th], 500);

            // return response()->json(['error' => 'Coba Kembali'], 500);
        }
    }

    public function orderList(Request $request)
    {

        if (auth()->user()->role === 'TENANT') {
            if (auth()->user()->tenant->is_derek > 0) {
                $data = $this->orderListDerek($request);

                $dataRes = TrOrderResourceDerek::collection($data);

                $sharingCodeSum = collect($dataRes)->flatMap(function ($item) {
                    return collect($item['sharing_code'])->zip($item['sharing_amount']);
                })->groupBy(0)->map(function ($group) {
                    return $group->sum(1);
                })->toArray();

                $result = [
                    'sharing' => $sharingCodeSum ?? [],
                    'record' => $dataRes ?? []
                ];
                // $mergedArray = array_merge($sharingCodeSum ?? [], $result);

                return response()->json($result);
            }
        }
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
        $identifier = auth()->user()->id;
        if (auth()->user()->role === 'TENANT' && auth()->user()->tenant->is_supertenant < 1 && auth()->user()->tenant->supertenant_id > 0) {
            $data = $this->orderListMemberOfSupertenant($request);
            return $data;
        } else {
            $data = TransOrder::with('payment_method', 'payment', 'detil.product', 'tenant', 'casheer', 'trans_edc.bank')
                ->when($status = request()->status, function ($q) use ($status) {
                    if (is_array($status)) {
                        $q->whereIn('status', $status)->orwhereIn('status', json_decode($status[0]) ?? []);
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
                ->when($payment_method = request()->payment_method, function ($q) use ($payment_method) {
                    $q->where('payment_method_id', $payment_method);
                })
                ->when($customer_name = request()->customer_name, function ($q) use ($customer_name) {
                    $q->where('customer_name', $customer_name)->orwhere('nomor_name', $customer_name);
                })
                // ->when(auth()->user()->role == 'CASHIER', function ($q) use ($identifier) {
                //     $q->where('casheer_id', $identifier);
                // });
                ->when(auth()->user()->role == 'CASHIER', function ($q) use ($identifier) {
                    $q->where('casheer_id', $identifier);
                    // $q->where(function ($q) use ($identifier) {
                    //     $q->where('casheer_id', $identifier)->Orwhere('casheer_id',NULL);
                    // });                
                });
            $data = $data->whereIn('order_type', ['POS', 'SELF_ORDER', 'TAKE_N_GO'])->orderBy('created_at', 'DESC')->get();
            // $data = $data->orderByRaw($queryOrder)->orderBy('created_at', 'DESC')->get();

            return response()->json(TrOrderResource::collection($data));
        }
    }

    public function orderListDerek(Request $request)
    {

        $data = TransOrder::with('payment_method', 'payment', 'detil.product', 'tenant', 'casheer', 'trans_edc.bank', 'detilDerek.detail', 'detilDerek.refund', 'Compare', 'Compare.detilReport')
            ->when($status = request()->status, function ($q) use ($status) {
                if (is_array($status)) {
                    $q->whereIn('status', $status)->orwhereIn('status', json_decode($status[0]) ?? []);
                } else {
                    $q->where('status', $status);
                }
            })->when($status_derek = request()->status_derek, function ($q) use ($status_derek) {
                $q->whereHas('detilDerek', function ($qq) use ($status_derek) {
                    $qq->where('is_solve_derek', $status_derek);
                });
            })
            ->when($start_date = $request->start_date, function ($q) use ($start_date) {
                // dd(date("Y-m-d", strtotime($start_date)));
                $q->whereDate('created_at', '>=', date("Y-m-d", strtotime($start_date)));
            })
            ->when($end_date = $request->end_date, function ($q) use ($end_date) {
                $q->whereDate('created_at', '<=', date("Y-m-d", strtotime($end_date)));
            })
            ->when($statusnot = $request->statusnot, function ($q) use ($statusnot) {
                if (is_array($statusnot)) {
                    // $jsonArray = str_replace(['[', ']', '"'], '', $statusnot);
                    // $array = explode(',', $jsonArray[0]);
                    $q->whereNotIn('status', $statusnot);
                } else {
                    $q->whereNotIn('status', $statusnot);
                }
            })
            ->when($filter = $request->filter, function ($q) use ($filter) {
                $q->where('order_id', 'like', "%$filter%");
            })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
                $q->where('tenant_id', $tenant_id);
            })->when($order_type = $request->order_type, function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })
            ->when($customer_name = $request->customer_name, function ($q) use ($customer_name) {
                $q->where('customer_name', $customer_name)->orwhere('nomor_name', $customer_name);
            })
            ->when($payment_method = request()->payment_method, function ($q) use ($payment_method) {
                $q->where('payment_method_id', $payment_method);
            })
            ->when($id = $request->id, function ($q) use ($id) {
                $q->where('id', $id);
            })->get();


        // ->where('detil_derek.is_solve_derek', 3)->get();
        return $data;
    }

    public function orderHistory(Request $request)
    {
        $identifier = auth()->user()->id;

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

        $data = TransOrder::with('payment_method', 'payment', 'detil.product', 'tenant', 'casheer', 'trans_edc.bank')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when($status = request()->status, function ($q) use ($status) {
                if (is_array($status)) {
                    // $jsonArray = str_replace(['[', ']', '"'], '', $status);
                    // $array = explode(',', $jsonArray[0]);
                    $q->whereIn('status', $status);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($start_date = request()->start_date, function ($q) use ($start_date) {
                // dd(date("Y-m-d", strtotime($start_date)));
                $q->whereDate('created_at', '>=', date("Y-m-d", strtotime($start_date)));
            })
            ->when($end_date = request()->end_date, function ($q) use ($end_date) {
                $q->whereDate('created_at', '<=', date("Y-m-d", strtotime($end_date)));
            })
            ->when($statusnot = request()->statusnot, function ($q) use ($statusnot) {
                if (is_array($statusnot)) {
                    // $jsonArray = str_replace(['[', ']', '"'], '', $statusnot);
                    // $array = explode(',', $jsonArray[0]);
                    $q->whereNotIn('status', $statusnot);
                } else {
                    $q->whereNotIn('status', $statusnot);
                }
            })
            ->when($filter = request()->filter, function ($q) use ($filter) {
                $q->where('order_id', 'like', "%$filter%");
            })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
                $q->where('tenant_id', $tenant_id);
            })->when($order_type = $request->order_type, function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })
            ->when($customer_name = request()->customer_name, function ($q) use ($customer_name) {
                $q->where('customer_name', $customer_name)->orwhere('nomor_name', $customer_name);
            })
            ->when(auth()->user()->role == 'CASHIER', function ($q) use ($identifier) {
                // $q->where('casheer_id', $identifier);
                $q->where(function ($q) use ($identifier) {
                    $q->where('casheer_id', $identifier)->Orwhere('casheer_id', NULL);
                });
            });

        $data = $data->orderByRaw($queryOrder)->orderBy('created_at', 'DESC')->get();
        // $datax = $data->get();
        // dd($data);
        return response()->json(TrOrderResource::collection($data));
    }

    public function orderById($id)
    {
        $super_tenant_checker = auth()->user()->tenant->is_supertenant;
        $super_tenant_id = auth()->user()->tenant->supertenant_id;
        if ($super_tenant_checker < 1 && $super_tenant_id > 0) {
            $data = $this->orderByIdMemberSupertenant($id);
            return $data;
        }
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
                    $cash = [
                        'cash' => $request->cash,
                        'total' => $data->total,
                        'kembalian' => $request->cash - $data->total
                    ];
                    $payment->data = $cash;
                    $payment->payment = $cash;
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
                    $payment->payment = $payment_payload;
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
            Log::error($th->getMessage());
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

        if ($request->status == TransOrder::READY && $data->order_type === 'SELF_ORDER') {
            $tenant = Tenant::where('id', $data->tenant_id)->firstOrFail();
            switch ($tenant?->in_selforder) {
                case 1:
                    // $data->status = TransOrder::WAITING_CONFIRMATION_TENANT;
                    break;

                case 2:
                    // $data->status = TransOrder::DONE;
                    break;

                case 3:
                    // $data->status = TransOrder::WAITING_CONFIRMATION_TENANT;
                    $data->status = TransOrder::DONE;
                    break;

                case 4:
                    $data->status = TransOrder::DONE;
                    break;

                default:
                    return response()->json(['error' => 'Hubungi Admin Untuk Aktivasi Fitur Self ORder'], 422);
                    break;
            }
            // $data->status = TransOrder::DONE;
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

    public function CallbackLinkAjaQRIS(Request $request)
    {

        try {
            // log::info('Callback LA');
            $trans = TransOrder::with('payment')->where('payment_method_id', 4)->where('order_id', 'like', '%' . $request->msg)->first();
            // log::info(['Callback LA', $trans, $request]);

            if (!$trans) {
                // temp
                $data = new CallbackLA();
                DB::beginTransaction();
                $data->trans_order_id = 'dump';
                $data->data = json_encode($request->all());
                $data->save();
                DB::commit();
                $datax = [
                    "responseCode" => "00",
                    "transactionID" => $request->msg,
                    "notificationMessage" => "Transaksi Sukses"
                ];
                return response()->json($datax);
                //
                // $datax = [
                //     "responseCode" => "03",
                //     "transactionID" => $request->msg,
                //     "notificationMessage" => "Dont Try Bro!"
                // ];
                // return response($datax, 422);
            }
            $data = new CallbackLA();
            $pay = TransPayment::where('trans_order_id', $trans->id)->first();
            DB::beginTransaction();
            $data->trans_order_id = $trans->id;
            $data->data = json_encode($request->all());
            $pay->refnum = $request->additional_data[0]['value'] ?? NULL;
            $pay->issuer_name = $request->issuer_name ?? NULL;

            $pay->orderid_sof = $request?->trx_id ?? NULL;
            $pay->save();
            $data->save();
            DB::commit();

            $datax = [
                "responseCode" => "00",
                "transactionID" => $request->msg,
                "notificationMessage" => "Transaksi Sukses"
            ];
            return response()->json($datax);
        } catch (\Throwable $th) {
            // DB::rollback();
            Log::error($th->getMessage());
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function orderIdentifier(Request $request, $id)
    {
        try {

            DB::beginTransaction();
            $data = TransOrder::findOrfail($request->id);
            $data->nomor_name = $request->nomor_name;
            $data->consume_type = $request->consume_type;
            $data->save();
            DB::commit();
            return response()->json([
                'message' => 'Sukses',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function UpdateSharingMode()
    {

        $cred = request()->cred;
        if ($cred === 'rahmatnurisni') {

            $data = TransOrder::whereNull('sharing_code')->whereNotIn('order_type', ['ORDER_TRAVOY'])->get();
            foreach ($data as $v) {
                // dump ($v->id);
                $v->update([
                    'sharing_code' => '["' . $v->tenant_id . '"]', // Set the desired value
                    'sharing_amount' => '[' . $v->total . ']', // Set the desired value
                    'sharing_proportion' => '[100]', // Set the desired value

                ]);
            }

            $log = CallbackLA::where('trans_order_id', '!=', 'dump')->get();
            foreach ($log as $v) {
                $datax = TransPayment::where('trans_order_id', $v->trans_order_id)->first();
                // dump($data);
                // dump($v->data);
                if ($datax) {
                    $gex = json_decode($v->data);
                    // dump ($gex->issuer_name);
                    $datax->update([
                        'issuer_name' => $gex->issuer_name // Set the desired value
                    ]);
                }
            }


            return response()->json(['result' => 'oke'], 200);
        } else {
            return response()->json(['result' => 'ihiy dont try'], 422);
        }
    }
}
