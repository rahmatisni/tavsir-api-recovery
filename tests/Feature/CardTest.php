<?php

use App\Models\Bind;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->endpoint = '/api/card';

});

it('bind card', function($is_snap){
    $paylaod = [
        'customer_id' => 1,
        'sof_code' => 'BRI',
        'customer_name' => 'customer test',
        'card_no' => Str::random(16),
        'phone' => '08123456789',
        'email' => 'test@example.com',
        'exp_date' => date('my'),
        'payment_method_id' => PaymentMethod::where('is_snap', $is_snap)->first()->id
    ];
    $result = $this->post($this->endpoint, $paylaod);
    $result->assertSuccessful();
})
->with([
    true,
    false
]);

it('bind validate card', function($is_snap){
    if($is_snap) {
        $id = 1;
    } else {
        $id = 2;
    }
    $result = $this->put($this->endpoint.'/'.$id,[
        'otp' => 999999
    ]);
    $result->assertSuccessful();
})
->with([
    true,
    false,
]);

it('unbind card snap', function($is_snap){
    if($is_snap) {
        $id = 1;
    } else {
        $id = 2;
    }

    $result = $this->delete($this->endpoint.'/'.$id,[
        'otp' => 999999
    ]);
    $result->assertSuccessful();
    $bind = Bind::where('id', $id)->first();
    $this->assertNull($bind);
})->with([
    true,
    false,
]);

