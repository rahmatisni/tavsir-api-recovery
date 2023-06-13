<?php

namespace App\Console\Commands;

use App\Services\External\KiosBankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GetTokenKiosbankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kiosbank:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gent token kios';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(protected KiosBankService $kiosbankService)
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
        try {
            Log::info("Cron get token kios");
            $session = $this->kiosbankService->signOn();
            Redis::set('session_kios_bank', $session);
    
            //lifetime 1 day
            $lifetime = 86400;
            Redis::expire('session_kios_bank', $lifetime);
            Log::info("Cron token kios updated");
        } catch (\Throwable $th) {
            Log::info("Cron token kios error!".$th->getMessage());
        }
    }
}
