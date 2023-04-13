<?php

namespace App\Console\Commands;

use App\Models\PgJmto;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OrderExpireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek order expire';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("Cron order expire is working fine!");
        // 1. order->type :: ORDER_TRAVOY
        // 2. order->status :: WAITING_PAYMENT
        // 3. order->payment->exp_date        <         curr_date  => lebih dari 1 hari
        // 3. order->payment do cek status PG

        // TRUE (pay_status = 1)
        // 4. set order->status :: payment success
        // FALSE (pay_status = 0)
        // 4. set order->status::cancel

        $data = TransOrder::where('order_type', TransOrder::ORDER_TRAVOY)
            ->where('status', TransOrder::WAITING_PAYMENT)
            ->has('payment')
            ->whereHas('payment_method', function($q){
                $q->where('name','LIKE','%Virtual Account%');
            })
            ->get();

        

        foreach ($data as $value) {
            $expire_data = Carbon::create($value->payment->data['exp_date'])->addHours(24);
            if (Carbon::now()->endOfDay() >= $expire_data) 
            {
                $res = PgJmto::vaStatus(
                    $value->payment->data['sof_code'],
                    $value->payment->data['bill_id'],
                    $value->payment->data['va_number'],
                    $value->payment->data['refnum'],
                    $value->payment->data['phone'],
                    $value->payment->data['email'],
                    $value->payment->data['customer_name'],
                    $value->sub_merchant_id
                );
                if ($res['status'] == 'success') {
                    Log::info("Order $value->id status success");

                    $res_data = $res['responseData'];
                    if ($res_data['pay_status'] == '1') {
                        Log::info("Order $value->id pay status 1");
                        $value->status = TransOrder::PAYMENT_SUCCESS;
                    }

                    if ($res_data['pay_status'] == '0') {
                        Log::info("Order $value->id pay status 0");
                        $value->status = TransOrder::CANCEL;
                    }
                    $value->save();
                    $data->save();

                }
                Log::info("Order $value->id status not success");
            }
        }
    }
}
