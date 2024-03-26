<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $order_type = $this->faker->randomElement([TransOrder::ORDER_TAKE_N_GO, TransOrder::POS]);
        $tenant = Tenant::get()->random();
        $status = $this->faker->randomElement([
            'CART',
            'PENDING',
            'PAYMENT_SUCCESS',
            'WAITING_OPEN',
            'WAITING_CONFIRMATION',
            'WAITING_PAYMENT',
            'PREPARED',
            'READY',
            'DONE',
            'CANCEL',
        ]);

        $created_at = $this->faker->dateTimeBetween('-2 years', 'now');
        $sub_total = $this->faker->randomElement([
            10000,
            20000,
            30000,
            40000,
            50000,
            60000,
            70000,
            80000,
            90000,
            100000,
        ]);
        $fee = $this->faker->randomElement([0, 1000, 2000]);
        $service_fee = $this->faker->randomElement([0, 2000]);
        $total = $sub_total + $fee + $service_fee;
        return [
            'order_type' => $order_type,
            'order_id' => $order_type == TransOrder::ORDER_TAKE_N_GO ? 'TNG-'.$this->faker->date('YmdHis') : 'POS-'.$this->faker->date('YmdHis'),
            'tenant_id' => $tenant->id,
            'rest_area_id' => $tenant->rest_area_id,
            'business_id' => $tenant->business_id,
            'customer_id' => $this->faker->randomElement([1,2,3,4,5,6,7,8,9,10]),
            'customer_name' => $this->faker->name,
            'customer_phone' => $this->faker->phoneNumber,
            'merchant_id' => $this->faker->randomElement([1,2,3,4,5,6,7,8,9,10]),
            'sub_merchant_id' => $this->faker->randomElement([1,2,3,4,5,6,7,8,9,10]),
            'status' => $status,
            'canceled_by' => $status == 'CANCEL' ? $this->faker->randomElement(['CASHEER', 'CUSTOMER']) : null,
            'canceled_name' => $status == 'CANCEL' ? $this->faker->name : null,
            'code_verif' => ($status == 'READY' || $status == 'DONE')? $this->faker->randomNumber(4) : null,
            'created_at' => $created_at,
            'confirm_date' => $status == 'WAITING_PAYMENT' ? $created_at : null,
            'pickup_date' => $status == 'DONE' ? $created_at : null,
            'rating' => $status == 'DONE' ? $this->faker->randomElement([1,2,3,4,5]) : null,
            'rating_comment' => $status == 'DONE' ? $this->faker->sentence : null,
            'sub_total' => $sub_total,
            'fee' => $fee,
            'service_fee' => $service_fee,
            'total' => $total,
            'payment_method_id' => PaymentMethod::get()->random()->id,
            'casheer_id' => User::get()->random()->id,
        ];
    }
}
