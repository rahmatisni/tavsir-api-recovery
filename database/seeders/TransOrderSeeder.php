<?php

namespace Database\Seeders;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\TransPayment;
use App\Models\Voucher;
use Faker\Factory;
use Illuminate\Database\Seeder;

class TransOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();
        TransOrder::factory()->count(500)->create()->each(function ($transOrder) use ($faker) {
            $product = Product::byType(ProductType::PRODUCT)->where('tenant_id', $transOrder->tenant_id)->get();
            $count = $product->count();
            $random_get = $faker->numberBetween(1, $count);
            $product->random($random_get)->each(function ($product) use ($transOrder, $faker) {
                $qty = $faker->numberBetween(1, 10);
                $price = $product->price;
                $total = $qty * $price;
                $customize_product = $product->customize()->get()->random();
                $customize_order = array();
                if ($customize_product) {
                    $pilihan = collect($customize_product->pilihan)->random();
                    if ($pilihan) {
                        $customize_order[] = [
                            'customize_id' => $customize_product->id,
                            'customize_name' => $customize_product->name,
                            'pilihan_id' => $pilihan->id,
                            'pilihan_name' => $pilihan->name,
                            'pilihan_price' => $pilihan->price,
                        ];
                        $price += $pilihan->price;
                    }
                }
                $detil = new TransOrderDetil();
                $detil->trans_order_id = $transOrder->id;
                $detil->product_id = $product->id;
                $detil->product_name = $product->name;
                $detil->customize = json_encode($customize_order);
                $detil->price_capital = $product->price_capital;
                $detil->base_price = $product->price;
                $detil->price = $price;
                $detil->qty = $qty;
                $detil->total_price = $qty * $price;
                $detil->save();

                $transOrder->sub_total += $detil->total_price;
            });
            $transOrder->total = $transOrder->sub_total + $transOrder->fee + $transOrder->service_fee;
            $transOrder->save();

            if (
                $transOrder->status == TransOrder::PAYMENT_SUCCESS ||
                $transOrder->status == TransOrder::PREPARED ||
                $transOrder->status == TransOrder::READY ||
                $transOrder->status == TransOrder::DONE
            ) {
                $payment = new TransPayment();
                $payment->trans_order_id = $transOrder->id;
                if ($transOrder->payment_method_id == 4 || $transOrder->payment_method_id == 5) {
                    $payment->data = [
                        'order_id' => $transOrder->order_id,
                        'order_name' => $transOrder->order_type == TransOrder::ORDER_TAKE_N_GO ? 'Take N Go' : 'Tavasir',
                        'amount' => $transOrder->total,
                        'desc' => $transOrder->tenant->name ?? '',
                        'phone' => $transOrder->customer_phone,
                        'email' => $faker->email,
                        'customer_name' => $transOrder->customer_name,
                        'voucher' => Voucher::get()->random()->id,
                    ];
                } elseif ($transOrder->payment_method_id == 6) {
                    $payment->data = [
                        'cash' => $transOrder->cash += 10000,
                        'total' => $transOrder->total,
                        'kembalian' => $transOrder->cash - $transOrder->total
                    ];
                } else {
                    $payment->data = [
                        'sof_code' =>  $transOrder->payment_method->code_sof,
                        'va_number' => (string)$faker->numberBetween(1000000000000000, 9999999999999999),
                        'bill' => (string)$transOrder->sub_total + $transOrder->fee,
                        'fee' => (string)$transOrder->service_fee,
                        'amount' => (string) $transOrder->total,
                        'bill_id' => $transOrder->order_id,
                        'bill_name' => $transOrder->order_type == TransOrder::ORDER_TAKE_N_GO ? 'Take N Go' : 'Tavasir',
                        'desc' => $transOrder->tenant->name ?? '',
                        "exp_date" =>  $transOrder->created_at,
                        "refnum" =>  'VA' . $transOrder->created_at,
                        'phone' => $transOrder->customer_phone,
                        'email' => $faker->email,
                        'customer_name' => $transOrder->customer_name,
                    ];
                }
                $payment->save();
            }
        });
    }
}
