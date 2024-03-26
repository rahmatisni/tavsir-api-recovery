<?php

use App\Models\Bind;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\Voucher;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->endpoint = '/api/tavsir';
    $this->loginAs();
});

it('create payment', function($code_name, $status, $structure = null, $status_2 = null, $structure_2 = null, $otp= null){
    $this->assertTrue(true);
    $payment = PaymentMethod::where('code_name', $code_name)->first();
    $tenant = Tenant::first();
    $order = TransOrder::factory()
    ->hasDetil()
    ->create([
        'tenant_id' => $tenant->id,
        'order_type' => TransOrder::POS,
        'status' => TransOrder::WAITING_PAYMENT
    ]);
    $payload = [
        'payment_method_id' => $payment->id,
        ...$this->generateCustomer()
    ];

    if(Str::contains($code_name, 'pg_dd')){
        $card = Bind::factory()->create();
        $payload['card_id'] = $card->id;
    }

    if($code_name == 'tav_qr'){
        $voucher = Voucher::factory()->create([
            'rest_area_id' => $tenant->rest_area_id,
            'is_active' => true
        ]);
        $payload['voucher'] = $voucher->hash;
    }

    $response = $this->post($this->endpoint."/create-payment/{$order->id}",$payload);

    if($response->status() != $status){
        dump($response->json(), $order->payment?->toArray());
    }
    $response->assertStatus($status);
    
    if($structure){
        $response->assertJsonStructure($structure);
    }

    $responseCekStatus = $this->get($this->endpoint."/payment-status/{$order->id}?otp=123");

    if($status_2){
        if($responseCekStatus->status() != $status_2){
            dump($responseCekStatus->json());
        }
        $responseCekStatus->assertStatus($status_2);

        if($responseCekStatus->status() == 200){
            if($structure_2){
                $responseCekStatus->assertJsonStructure($structure_2);
                $this->assertEquals(1, $responseCekStatus['responseData']['pay_status']);
            }
            $this->assertEquals(TransOrder::DONE, TransOrder::find($order->id)->status);
        }

        if($responseCekStatus->status() == 422){
            $responseCekStatus->assertJsonStructure($structure_2);
            $this->assertEquals(0, $responseCekStatus->json()['responseData']['pay_status']);
            $this->assertEquals(TransOrder::WAITING_PAYMENT, TransOrder::find($order->id)->status);
        }
    }

})->with([
    [
        'snap_va_mandiri',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'bill_name',
                'exp_date',
                'phone',
                'email',
                'customer_name',
                'desc'
            ]
        ],
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill',
                'bill_id',
                'bill_name',
                'exp_date',
                'phone',
                'email',
                'customer_name',
                'fee',
                'responseCode',
                'responseMessage',
                'desc',
                'pay_status',
            ]
        ],
    ],
    [
        'pg_va_mandiri',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'bill_id',
                'va_number',
                'amount',
                'exp_date',
                'phone',
                'email',
                'customer_name',
                'desc'
            ]
        ],
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'pay_status',
                'amount',
                'name',
                'desc',
                'exp_date',
                'refnum',
            ],
        ]
    ],
    [
        'pg_va_bri',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'bill_name',
                'exp_date',
                'phone',
                'email',
                'customer_name',
                'desc'
            ]
        ],
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'pay_status',
                'amount',
                'name',
                'desc',
                'exp_date',
                'refnum',
            ],
        ]
    ],
    [
        'pg_va_bni',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'bill_name',
                'exp_date',
                'phone',
                'email',
                'customer_name',
                'desc'
            ]
        ],
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'va_number',
                'bill_id',
                'pay_status',
                'amount',
                'name',
                'desc',
                'exp_date',
                'refnum',
            ],
        ]
    ],
    [
        'pg_dd_mandiri',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'card_no',
                'bill',
                'fee',                
                'amount',                
                'trxid',
                'remarks',
                'refnum',
                'email',
                'phone',
                'customer_name',
                'bind_id',                
                'card_id',        
            ]
        ],
        200,
        null
    ],
    [
        'pg_dd_bri',
        200,
        [
            'status',
            'rc',
            'rcm',
            'responseData' => [
                'sof_code',
                'card_no',
                'bill',
                'fee',                
                'amount',                
                'trxid',
                'remarks',
                'refnum',
                'email',
                'phone',
                'customer_name',
                'bind_id',                
                'card_id',        
            ]
        ],
        200,
        null
    ],
    [
        'pg_link_aja',
        200,
        [
            "status",
            "rc",
            "msg",
            "responseData" => [
                "sof_code",
                "amount",
                "trxid",
                "desc",
                "opt_key",
                "opt_value",
                "LINK_URL",
                "refnum"
            ]
        ]
    ],
    [
        'tav_qr',
        200,
        null,
    ],
]);

