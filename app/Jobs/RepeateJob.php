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

class RepeateJob implements ShouldQueue
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
            Log::info('Repeate begin');
            $trans_order = TransOrder::with(['log_kiosbank'])->find($this->data['id']);
            if($trans_order->status == TransOrder::DONE){
                return;
            }
            $log_kios = $trans_order->log_kiosbank?->inquiry ?? null;
            if(!$log_kios){
                Log::info('Reoeate no data inquiry ');
                return;
            }
            $count = $log_kios['repeate_count'] ?? 0;
            $count = $count +1;
            $log_kios['repeate_count'] = $count;

            $trans_order->log_kiosbank()->updateOrCreate(['trans_order_id' => $trans_order->id], [
                'data' => $log_kios,
                'inquiry' => $log_kios,
            ]);

            if($count > 3){
                Log::info('Repeate count telah mencapai '.$count);

                return;
            }
            
            $res_jatelindo = JatelindoService::repeat($log_kios, $trans_order);
            $result_jatelindo = $res_jatelindo->json();
            $rc = $result_jatelindo['bit39'] ?? '';
            Log::info('Repeate ke-'.$count.' rc = '.$rc);
            if($rc == '18' || $rc == '13' || $rc == '96'){
                Log::info('Dispatch RepeateJob reason rc '.$rc);
                $trans_order->log_kiosbank()->updateOrCreate(['trans_order_id' => $trans_order->id], [
                    'data' => $result_jatelindo,
                    'payment' => $result_jatelindo
                ]);
                RepeateJob::dispatch($this->data)->delay(now()->addSecond(35));
                $trans_order->status = TransOrder::READY;
                $trans_order->save();
                return;
            }

            if($rc == '00'){
                $trans_order->status = TransOrder::DONE;
                $trans_order->save();
                return;
            }
        } catch (\Throwable $e) {
            Log::info('Repeate timeout : '. $e->getMessage());
            $trans_order->log_jatelindo()->updateOrCreate([
                'trans_order_id' => $trans_order->trans_order_id,
                'type' => LogJatelindo::repeat,
                'request' => $log_kios,
                'response' => [$e->getMessage()],
            ]);
            RepeateJob::dispatch($this->data)->delay(now()->addSecond(35));
            Log::info('Dispatch RepeateJob reason timeout');
            $trans_order->status = TransOrder::READY;
            $trans_order->save();
            return;
        }
    }
}
