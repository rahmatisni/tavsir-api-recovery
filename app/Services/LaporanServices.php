<?php

namespace App\Services;

use App\Exports\LaporanTransaksiExport;
use App\Http\Requests\DownloadLaporanRequest;
use App\Http\Resources\LaporanMetodePembayaranResource;
use App\Http\Resources\LaporanOperationalResource;
use App\Http\Resources\LaporanPenjualanResource;
use App\Http\Resources\LaporanTransaksiResource;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TransOperational;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use Carbon\Carbon;

class LaporanServices
{
    public function penjualanKategori(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;
        $super_tenant_id = auth()->user()->tenant->is_supertenant ?? null;
        $super_tenant_id = $super_tenant_id === 0 || $super_tenant_id === NULL ? NULL : auth()->user()->tenant->id;


        if (auth()->user()->role === 'TENANT') {
            $data = TransOrderDetil::whereHas(
                'trans_order',
                function ($q) use ($tanggal_awal, $tanggal_akhir, $super_tenant_id, $business_id, $rest_area_id, $tenant_id) {
                    return $q->where('status', TransOrder::DONE)
                        ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                            return $qq->whereBetween(
                                'created_at',
                                [
                                    $tanggal_awal,
                                    $tanggal_akhir . ' 23:59:59'
                                ]
                            );
                        })->when($super_tenant_id, function ($qq) use ($super_tenant_id) {
                            return $qq->where('supertenant_id', $super_tenant_id);
                        });
                }
            )->whereHas('product', function ($qq) use ($tenant_id) {
                if (auth()->user()->tenant->is_supertenant != null) {
                    if ($tenant_id != null) {
                        $qq->where('tenant_id', $tenant_id);
                    }
                } else {
                    $qq->where('tenant_id', auth()->user()->tenant->id);
                };
            })->get()->groupBy('product.category.id');
        

        } else {

            $data = TransOrderDetil::whereHas('trans_order', function ($q) use ($tanggal_awal, $tanggal_akhir, $tenant_id, $rest_area_id, $business_id) {
                return $q->where('status', TransOrder::DONE)
                    ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                        return $qq->whereBetween(
                            'created_at',
                            [
                                $tanggal_awal,
                                $tanggal_akhir . ' 23:59:59'
                            ]
                        );
                    })->when($tenant_id, function ($qq) use ($tenant_id) {
                        return $qq->where('tenant_id', $tenant_id);
                    })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                        return $qq->where('rest_area_id', $rest_area_id);
                    })->when($business_id, function ($qq) use ($business_id) {
                        return $qq->where('business_id', $business_id);
                    });
            })->with('product.category')->get()
                ->groupBy('product.category.id');
        }
        $hasil = [];
        $sum_total_transaksi = 0;
        $sum_jumlah_transaksi = 0;
        foreach ($data as $k => $i) {
            $jumlah_transaksi = $i->sum('qty');
            $total_transaksi = $i->sum('total_price');

            $sum_jumlah_transaksi += $jumlah_transaksi;
            $sum_total_transaksi += $total_transaksi;

            array_push($hasil, [
                'kategori' => $k,
                'tenant_name' => $i[0]['product']['tenant']['name'] ?? '',
                'jumlah_terjual' => $jumlah_transaksi,
                'pendapatan_kategori' => $total_transaksi,
            ]);
        }

        if ($data->count() == 0) {
            abort(404);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'sum_jumlah_transaksi' => $sum_jumlah_transaksi,
            'sum_total_transaksi' => $sum_total_transaksi,
            'data' => $hasil,
        ];
        return $record;
    }

    public function operational(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOperational::when(($tanggal_awal && $tanggal_akhir), function ($q) use ($tanggal_awal, $tanggal_akhir) {
            return $q->whereBetween(
                'created_at',
                [
                    $tanggal_awal,
                    $tanggal_akhir . ' 23:59:59'
                ]
            );
        })->whereHas('tenant', function ($qq) use ($tenant_id, $rest_area_id, $business_id) {
            return $qq->when($tenant_id, function ($qq) use ($tenant_id) {
                return $qq->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->when($business_id, function ($qq) use ($business_id) {
                return $qq->where('business_id', $business_id);
            });
        })
            ->whereNotNull('end_date')
            ->get();
        if ($data->count() == 0) {
            abort(404);
        }
        // $sharing = [];
        // $sharing_cashboax = $data->first()->trans_cashbox?->sharing ?? [];
        // dd($data->first)->trans_cashbox;
        // $sharing_cashboax = [];
        // $count = 0;
        // foreach ($sharing_cashboax as $key => $value) {
        //     $label = 'Investor '.$count;
        //     if($count == 0) {
        //         $label = Tenant::where('id', $key)->first()->name ?? 'Tenant tidak ada';
        //     }

        //     array_push($sharing, [
        //         'label' => $label,
        //         'value' => $value,
        //     ]);
        //     $count ++;
        // }
        $sharing_count = 0;
        $sharing_data = [];
        foreach ($data as $item) {
            $cek = $item?->trans_cashbox?->sharing ?? null;
            if ($cek) {
                $array = json_decode($cek, true);
                $array = array_keys($array);
                $counter = count($array);
                if ($counter > $sharing_count) {
                    $sharing_count = $counter;
                }
                $sharing_data = array_unique(array_merge($sharing_data, $array));
            }
        }

        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_qr' => $data->sum('trans_cashbox.rp_tav_qr'),
            'total_digital' => $data->sum('trans_cashbox.total_digital'),
            'total_tunai' => $data->sum('trans_cashbox.rp_cash'),
            'total_nominal_tunai' => $data->sum('trans_cashbox.cashbox'),
            'total_koreksi' => $data->sum('trans_cashbox.pengeluaran_cashbox'),
            'total_addon' => $data->sum('trans_cashbox.rp_addon_total'),
            'total_refund' => $data->sum('trans_cashbox.rp_refund'),
            'total' => $data->sum('trans_cashbox.rp_total'),
            'sharing_data' => $sharing_data, //$sharing,
            'sharing_count' => $sharing_count, //count($sharing),
            'record' => json_decode(LaporanOperationalResource::collection($data)->toJson()),
        ];
        return $record;
    }

    public function penjualan(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;
        $super_tenant_id = auth()->user()->tenant->is_supertenant ?? null;
        $super_tenant_id = $super_tenant_id === 0 || $super_tenant_id === NULL ? NULL : auth()->user()->tenant->id;

        if (auth()->user()->role === 'TENANT') {
            $data = TransOrderDetil::whereHas(
                'trans_order',
                function ($q) use ($tanggal_awal, $tanggal_akhir, $super_tenant_id, $business_id, $rest_area_id, $tenant_id) {
                    return $q->where('status', TransOrder::DONE)
                        ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                            return $qq->whereBetween(
                                'created_at',
                                [
                                    $tanggal_awal,
                                    $tanggal_akhir . ' 23:59:59'
                                ]
                            );
                        })->when($super_tenant_id, function ($qq) use ($super_tenant_id) {
                            return $qq->where('supertenant_id', $super_tenant_id);
                        });
                }
            )->whereHas('product', function ($qq) use ($tenant_id) {
                if (auth()->user()->tenant->is_supertenant != null && auth()->user()->tenant->is_supertenant != 0) {
                    if ($tenant_id != null) {
                        $qq->where('tenant_id', $tenant_id);
                    }
                } else {
                    $qq->where('tenant_id', auth()->user()->tenant->id);
                };
            })->get();
            // ->groupBy('product_id');
            return($data);

        } else {
            $data = TransOrderDetil::whereHas(
                'trans_order',
                function ($q) use ($tanggal_awal, $tanggal_akhir, $tenant_id, $rest_area_id, $business_id) {
                    return $q->where('status', TransOrder::DONE)
                        ->when(($tanggal_awal && $tanggal_akhir), function ($qq) use ($tanggal_awal, $tanggal_akhir) {
                            return $qq->whereBetween(
                                'created_at',
                                [
                                    $tanggal_awal,
                                    $tanggal_akhir . ' 23:59:59'
                                ]
                            );
                        })->when($tenant_id, function ($qq) use ($tenant_id) {
                            return $qq->where('tenant_id', $tenant_id);
                        })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                            return $qq->where('rest_area_id', $rest_area_id);
                        })->when($business_id, function ($qq) use ($business_id) {
                            return $qq->where('business_id', $business_id);
                        });
                }
            )->get();
        }

        if ($data->count() == 0) {
            abort(404);
        }
        // $datax = [];

        // foreach ($data as $k => $i) {

        //     // dump($k);
        //     // foreach($i as $value){
        //     //     dump($value);

        //     // }
           
        //     // dd($i);
        //     // $jumlah_transaksi = $i->sum('qty');
        //     // $total_transaksi = $i->sum('total_price');

        //     // $sum_jumlah_transaksi += $jumlah_transaksi;
        //     // $sum_total_transaksi += $total_transaksi;

        //     array_push($datax, [
        //         "tenant_id"=> $i[end($i)]->product->tenant_id,
        //         "tenant_name"=> $i[end($i)]->product->tenant->name,
        //         "sku"=> $i[end($i)]->product->sku,
        //         "nama_product"=> $i[end($i)]->product_name,
        //         "nama_varian"=> $i[end($i)]->customize,
        //         "jumlah" => $i->sum('qty'),
        //         "harga"=>  $i[end($i)]->price,
        //         "pendapatan"=>  $i->sum('total_price'),
        //         'harga_varian' =>  $i[end($i)]->pilihan_price,
        //         'kategori' =>  $i[end($i)]->product->category->name ?? '',
        //     ]);
        // }
        
        // return($datax);



        $res = json_decode(LaporanPenjualanResource::collection($data)->toJson());
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_jumlah' => $data->sum('qty'),
            'total_pendapatan' => $data->sum('total_price'),
            'record' => $res
        ];
        return $record;
    }

    public function metodePembayaran(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;

        $data = TransOrder::Done()->when(
            ($tanggal_awal && $tanggal_akhir),
            function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            }
        )
            ->when($tenant_id, function ($q) use ($tenant_id) {
                return $q->where('tenant_id', $tenant_id);
            })->when($rest_area_id, function ($qq) use ($rest_area_id) {
                return $qq->where('rest_area_id', $rest_area_id);
            })->when($business_id, function ($qq) use ($business_id) {
                return $qq->where('business_id', $business_id);
            })
            ->with('payment_method')->get()
            ->groupBy('payment_method.name');

        $hasil = [];
        $sum_total_transaksi = 0;
        $sum_jumlah_transaksi = 0;
        $sum_total_addon = 0;
        foreach ($data as $k => $i) {
            $jumlah_transaksi = $i->count();
            $total_transaksi = $i->sum('sub_total');
            $total_addon = $i->sum('addon_total');

            $sum_jumlah_transaksi += $jumlah_transaksi;
            $sum_total_transaksi += $total_transaksi;
            $sum_total_addon += $total_addon;

            array_push($hasil, [
                'metode' => $k,
                'jumlah_transaksi' => $jumlah_transaksi,
                'total_addon' => $sum_total_addon,
                'total_transaksi' => $total_transaksi
            ]);
        }
        if ($data->count() == 0) {
            abort(404);
        }
        $record = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'sum_jumlah_transaksi' => $sum_jumlah_transaksi,
            'sum_total_transaksi' => $sum_total_transaksi,
            'record' => $hasil,
        ];

        return $record;
    }


    public function decode_manual($sharing_amount)
    {
        $pairStrings = explode(',', substr($sharing_amount, 1, -1));

        $resultArray = [];


        foreach ($pairStrings as $pairString) {
            $dotPosition = strpos($pairString, '.');

            // Define the number of characters to keep after the dot
            $charactersToKeep = 2;

            // Check if the dot was found and if there are enough characters after it
            if ($dotPosition !== false && $dotPosition + $charactersToKeep < strlen($pairString)) {
                // Trim characters after the dot + 2
                $trimmedString = substr($pairString, 0, $dotPosition + $charactersToKeep + 1);
            } else {
                // No trimming needed
                $trimmedString = $pairString;
            }


            $resultArray[] = $trimmedString;
        }
        return $resultArray;
    }
    public function transaksi(DownloadLaporanRequest $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;
        $tenant_id = $request->tenant_id;
        $rest_area_id = $request->rest_area_id;
        $business_id = $request->business_id;
        $order_type = $request->order_type;
        $payment_method_id = $request->payment_method_id;
        $super_tenant_id = ((auth()->user()->role === 'TENANT' && auth()->user()->tenant_id == $tenant_id) ? auth()->user()->supertenant_id : NULL);

        if ($super_tenant_id != NULL) {
            $raw_data = $data = TransOrder::with('tenant')
                ->when(
                    ($tanggal_awal && $tanggal_akhir),
                    function ($q) use ($tanggal_awal, $tanggal_akhir) {
                        return $q->whereBetween(
                            'created_at',
                            [
                                $tanggal_awal,
                                $tanggal_akhir . ' 23:59:59'
                            ]
                        );
                    }
                )
                ->when($super_tenant_id, function ($q) use ($super_tenant_id) {
                    return $q->where('supertenant_id', $super_tenant_id);
                })->orderBy('created_at')
                ->get();
        } else {
            $raw_data = $data = TransOrder::with('tenant')
                ->when(
                    ($tanggal_awal && $tanggal_akhir),
                    function ($q) use ($tanggal_awal, $tanggal_akhir) {
                        return $q->whereBetween(
                            'created_at',
                            [
                                $tanggal_awal,
                                $tanggal_akhir . ' 23:59:59'
                            ]
                        );
                    }
                )

                ->when($tenant_id, function ($q) use ($tenant_id) {
                    return $q->where('tenant_id', $tenant_id);
                })

                ->when($rest_area_id, function ($qq) use ($rest_area_id) {
                    return $qq->where('rest_area_id', $rest_area_id);
                })->when($business_id, function ($qq) use ($business_id) {
                    return $qq->where('business_id', $business_id);
                })->when($order_type, function ($qq) use ($order_type) {
                    return $qq->where('order_type', $order_type);
                })->when($payment_method_id, function ($qq) use ($payment_method_id) {
                    return $qq->where('payment_method_id', $payment_method_id);
                })
                ->orderBy('created_at')
                ->get();
        }
        // $raw_data = $data = TransOrder::with('tenant')
        //     ->when(
        //         ($tanggal_awal && $tanggal_akhir),
        //         function ($q) use ($tanggal_awal, $tanggal_akhir) {
        //             return $q->whereBetween(
        //                 'created_at',
        //                 [
        //                     $tanggal_awal,
        //                     $tanggal_akhir . ' 23:59:59'
        //                 ]
        //             );
        //         }
        //     )

        //     ->when($tenant_id, function ($q) use ($tenant_id) {
        //         return $q->where('tenant_id', $tenant_id);
        //     })

        //     ->when($rest_area_id, function ($qq) use ($rest_area_id) {
        //         return $qq->where('rest_area_id', $rest_area_id);
        //     })->when($business_id, function ($qq) use ($business_id) {
        //         return $qq->where('business_id', $business_id);
        //     })->when($order_type, function ($qq) use ($order_type) {
        //         return $qq->where('order_type', $order_type);
        //     })->when($payment_method_id, function ($qq) use ($payment_method_id) {
        //         return $qq->where('payment_method_id', $payment_method_id);
        //     })
        //     ->orderBy('created_at')
        //     ->get();
        $data = $raw_data->where('status', 'DONE');
        $data_w_refund = $raw_data->whereIn('status', ['DONE', 'REFUND']);
        if ($data->count() == 0) {
            abort(404);
        }

        $item_count = 0;
        $hasil = [];

        foreach ($data_w_refund as $value) {
            // $count = $value->detil->count();
            // $item_count += $count;

            if ($super_tenant_id != NULL) {
                $data_product = $value->detil;
                $referensi_product = Product::where('tenant_id', auth()->user()->tenant_id)->pluck('id')->toArray();
                $count = 0;
                $total_price = 0;
                foreach ($data_product as $values) {
                    $checker = in_array($values->product_id, $referensi_product);
                    if ($checker) {
                        $count = $count + $values->qty;
                        $total_price = $values->total_price + $total_price;
                    }
                }
                array_push($hasil, [
                    'tenant' => auth()->user()->name,
                    'waktu_transaksi' => (string) $value->created_at,
                    'id_transaksi' => $value->order_id,
                    'tenant_id' => auth()->user()->tenant_id,
                    'tenant_name' => auth()->user()->name,
                    'total_product' => $count,
                    'total_addon' => 0,
                    'total_sub_total' => $total_price,
                    'fee' => 0,
                    'service_fee' => 0,
                    'total' => $total_price,
                    'status' => $value->status,
                    'metode_pembayaran' => $value->payment_method->name ?? '',
                    'jenis_transaksi' => $value->labelOrderType(),
                    'sharing_code' => json_decode($value->sharing_code) ?? [],
                    'sharing_proportion' => json_decode($value->sharing_proportion) ?? [],
                    'sharing_amount' => $this->decode_manual(($value->sharing_amount)) ?? []
                ]);

            } else {
                $count = $value->detil->count();
                $item_count += $count;
                array_push($hasil, [
                    'tenant' => $value->tenant->name ?? '',
                    'waktu_transaksi' => (string) $value->created_at,
                    'id_transaksi' => $value->order_id,
                    'tenant_id' => $value->tenant_id,
                    'tenant_name' => $value->tenant->name ?? '',
                    'total_product' => $count,
                    'total_addon' => $value->addon_total ?? 0,
                    'total_sub_total' => $value->sub_total,
                    'fee' => $value->fee ?? 0,
                    'service_fee' => $value->service_fee ?? 0,
                    'total' => $value->total,
                    'status' => $value->status,
                    'metode_pembayaran' => $value->payment_method->name ?? '',
                    'jenis_transaksi' => $value->labelOrderType(),
                    'sharing_code' => json_decode($value->sharing_code) ?? [],
                    'sharing_proportion' => json_decode($value->sharing_proportion) ?? [],
                    'sharing_amount' => $this->decode_manual(($value->sharing_amount)) ?? []
                ]);
            }
        }

        $investor = $data->whereNotNull('sharing_code')->groupBy('sharing_code')->toArray();
        // dd($investor);
        $tempInvestor = [];
        $resulttempInvestor = [];

        if (count($investor) > 0) {
            foreach ($investor as $k => $v) {
                // dump($k);
                $arrk = json_decode($k);
                foreach ($arrk as $k2 => $v2) {
                    // dump($v2);
                    $temp = 0;
                    foreach ($v as $k3 => $v3) {
                        $value = json_decode($v3['sharing_amount']);
                        // dump($value[$k2]);
                        $temp += $value[$k2];
                    }
                    // dump($temp);
                    $tempInvestor[] = [$v2 => $temp];
                }
            }
            foreach ($tempInvestor as $item) {
                foreach ($item as $key => $value) {
                    // Check if the key exists in the result array
                    if (array_key_exists($key, $resulttempInvestor)) {
                        // If the key exists, add the value to the existing sum
                        $resulttempInvestor[$key] += $value;
                    } else {
                        // If the key does not exist, create a new entry in the result array
                        $resulttempInvestor[$key] = $value;
                    }
                }
            }
        }
        // $total_sharing = json_encode($tempInvestor);






        $data = [
            'nama_tenant' => Tenant::find($tenant_id)->name ?? 'Semua Tenant',
            'tanggal_awal' => $tanggal_awal ?? 'Semua Tanggal',
            'tanggal_akhir' => $tanggal_akhir ?? 'Semua Tanggal',
            'total_product' => $item_count,
            'total_addon' => $data->sum('addon_total'),
            'total_sub_total' => $data->sum('sub_total'),
            'fee' => $data->sum('fee'),
            'service_fee' => $data->sum('service_fee'),
            'total_total' => $data->sum('total'),
            'sharing' => $resulttempInvestor ?? [],
            'record' => $hasil

        ];

        return $data;
    }
}
