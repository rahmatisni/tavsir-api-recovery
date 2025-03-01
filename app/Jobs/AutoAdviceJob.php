<?php

namespace App\Jobs;

use App\Models\LogJatelindo;
use App\Models\LogKiosbank;
use App\Models\TransOrder;
use App\Services\External\JatelindoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoAdviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected array $data,
        )
    {
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Auto Advice begin');
            $trans_order = TransOrder::with(['log_kiosbank'])->find($this->data['id']);
            if($trans_order->status == TransOrder::DONE){
                return;
            }
            $log_kios = $trans_order->log_kiosbank?->inquiry ?? ($trans_order->log_kiosbank?->data ?? null);
            if(!$log_kios){
                Log::info('Repeate no data inquiry ');
                return;
            }
            $log_kios['is_advice'] = true;
            $trans_order->log_kiosbank()->update(['data' => $log_kios, 'inquiry' => $log_kios]);
            $res_jatelindo = JatelindoService::advice($log_kios, $trans_order);
            $result_jatelindo = $res_jatelindo->json();
            $rc = $result_jatelindo['bit39'] ?? '';
            Log::info('Auto Advice rc = '.$rc);

            if($rc == '47' || $rc == '90'){
                Log::info('Dispatch RepeateJob reason rc '.$rc);
                $trans_order->log_kiosbank()->update(['data' => $result_jatelindo, 'payment' => $result_jatelindo]);
                // RepeateJob::dispatch($this->data)->delay(now()->addSecond(35));
                $trans_order->status = TransOrder::READY;
                $trans_order->save();
            }


            if($rc == '18' || $rc == '13' || $rc == '96'){
                Log::info('Dispatch RepeateJob reason rc '.$rc);
                $trans_order->log_kiosbank()->update(['data' => $result_jatelindo, 'payment' => $result_jatelindo]);
                RepeateJob::dispatch($this->data)->delay(now()->addSecond(35));
                $trans_order->status = TransOrder::READY;
                $trans_order->save();
            }

            if($rc == '00'){
                $trans_order->status = TransOrder::DONE;
                $trans_order->save();
                return;
            }
        } catch (\Throwable $e) {
            Log::info('Advice timeout : '. $e->getMessage());
            Log::info('Dispatch RepeateJob reason timeout');
            $trans_order->log_jatelindo()->updateOrCreate([
                'trans_order_id' => $trans_order->trans_order_id,
                'type' => LogJatelindo::repeat,
                'request' => $log_kios,
                'response' => [$e->getMessage()],
            ]);
            RepeateJob::dispatch($this->data)->delay(now()->addSecond(35));
            $trans_order->status = TransOrder::READY;
            $trans_order->save();
            return;
        }
    }
}
