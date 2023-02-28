<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use App\Models\PgJmto;
use Illuminate\Console\Command;

class SofCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sof:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PG list source of fund';

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
        $res = PgJmto::sofList();
        dd($res);
        if($res->successful())
        {
            $res = $res->json();
            if($res['status'] == 'success')
            {
                $data = $res['responseData'];
                foreach ($data as $key => $value) {
                    PaymentMethod::updateOrCreate(
                        [
                            'code_name' => strtolower('pg_'.$value['payment_method_code'].'_'.$value['code']),
                        ],
                        [
                            'sof_id' => $value['sof_id'],
                            'code' => $value['code'],
                            'name' => $value['name'],
                            'description' => $value['description'],
                            'payment_method_id' => $value['payment_method_id'],
                            'payment_method_code' => $value['payment_method_code'],
                        ]
                    );
                }
            }
        }
        return $res;
    }
}
