<?php

use App\Jobs\AutoAdviceJob;
use App\Jobs\RepeateJob;
use App\Models\TransOrder;
use App\Services\External\JatelindoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

it('can order pln', function ($mode, $status_order) {
    // Queue::fake();

    Http::fake([
        config('jatelindo.url') => Http::response([
            "bit49" => "360",
            "bit48" => "JTL53L3149876543211499999999110A6F831183B61FD503C666F94470511200007210ZEC9232360DA988749FD715F8HAMDANIE LESTALUHUANI    R1  000000900",
            "bit7" => "0228051120",
            "bit37" => "000000051120",
            "bit39" => "00",
            "bit3" => "380000",
            "bit12" => "051120",
            "bit4" => "000000000000",
            "bit13" => "0228",
            "bit2" => "053502",
            "bit11" => "051120",
            "mti" => "0210",
            "bit18" => "6012",
            "bit15" => "0228",
            "bit62" => "5151106123            060000",
            "bit42" => "200900100800000",
            "bit41" => "DEVJMT01",
            "bit32" => "008"
        ])
    ]);
    $response = $this->post('/api/travshop/info-pelanggan', [
        'meter_id' => '519999999900'
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'info' => [
                'Meter_ID',
                'Pelanggan_ID',
                'Flag',
                'Nama_Pelanggan',
                'Tarif',
                'Daya',
            ],
            'info_tambahan',
            'result_pln'
        ]
    ]);

    $result_pln = $response->json('data.result_pln');

    //can order token
    $response = $this->post('/api/kios-bank/uang-elektronik/order', [
        'customer_id' => 54330,
        'customer_name' => $this->faker()->name,     
        'customer_phone' => '08123456789',
        'phone' => 5199999999000,
        'code' => 20000,
        'result_pln' => $result_pln
    ]);
    $response->assertStatus(200);
    $trans_order_id = $response->json('id');

    //can get order
    $response = $this->get('/api/travshop/order/'.$trans_order_id);
    $response->assertStatus(200);

    //can create payment
    $response = $this->post('/api/travshop/create-payment/'.$trans_order_id, [
        "payment_method_id" => 9,
        "customer_id" => $this->faker->numberBetween(1, 100), 
        "customer_phone" => (string) $this->faker->numberBetween(1234567890, 9876543210),
        "customer_name" => $this->faker->name(),
        "customer_email" => $this->faker->email(),
    ]);
    $response->assertStatus(200);

    $this->resetHttpFake(); //reset fake http

    switch ($mode) {
        case 'success':
            Http::fake([
                config('jatelindo.url') => Http::response([
                    "bit49" => "360",
                    "bit48" => "JTL53L3149876543211499999999110A6F831183B61FD503C666F94470511200007210ZEC9232360DA988749FD715F8HAMDANIE LESTALUHUANI    R1  000000900",
                    "bit7" => "0228051120",
                    "bit37" => "000000051120",
                    "bit39" => "00", //success
                    "bit3" => "380000",
                    "bit12" => "051120",
                    "bit4" => "000000000000",
                    "bit13" => "0228",
                    "bit2" => "053502",
                    "bit11" => "051120",
                    "mti" => "0210",
                    "bit18" => "6012",
                    "bit15" => "0228",
                    "bit62" => "5151106123            060000",
                    "bit42" => "200900100800000",
                    "bit41" => "DEVJMT01",
                    "bit32" => "008"
                ])
            ]);
            break;
        
        case 'rc':
            Http::fake([
                config('jatelindo.url') => Http::response([
                    'bit39' => Arr::random(['13', '18', '96'])
                ])
            ]);
            break;
        
        default:
            # code...
            break;
    }
    //can cek status payment
    $response = $this->get('/api/travshop/payment-status/'.$trans_order_id);
    $response->assertStatus(200);
    $response->assertJsonPath('status',$status_order);
    if($status_order == TransOrder::READY){
        // Queue::assertPushed(AutoAdviceJob::class, function ($job) {
        //     return !is_null($job->delay);
        // });

        // $job = new AutoAdviceJob(['id' => $trans_order_id]);
        // $job->handle();
        // Queue::assertPushed(RepeateJob::class, function ($job) {
        //     return !is_null($job->delay);
        // });
        // $job = new RepeateJob(['id' => $trans_order_id]);
        // $job->handle();
    }
})
->with([
    [
        'rc',
        TransOrder::READY
    ],
    [
        'success',
        TransOrder::DONE,
    ]
])
->group('order-pln');