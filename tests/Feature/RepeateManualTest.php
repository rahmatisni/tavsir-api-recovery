<?php

use App\Jobs\Feature\Payment\Withdrawal\ExportWithdrawalMethodJob;
use App\Models\ClientProfile\ClientProfileBank;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->endpoint = '/api/travshop/repeate-manual';

});

it('can repate manual', function () {
    $data_log_kios = [
        // 'repeate_date' => '2024-06-19 16:26:49',
        // 'repeate_count' => 0,
    ];
    $res = [];
    $date_repeate = $data_log_kios['repeat_date'] ?? Carbon::now()->addMinute(6)->toDateTimeString();
    $count_repeate = $data_log_kios['repeate_count'] ?? 0;

    array_push($res, ['repeat_date' => $date_repeate, 'repeate_count' => $count_repeate ++]);
    array_push($res, ['repeat_date' => $date_repeate, 'repeate_count' => $count_repeate ++]);

    $date_repeate = $data_log_kios['repeat_date'] ?? Carbon::now()->addMinute(6)->toDateTimeString();
    $count_repeate = $data_log_kios['repeate_count'] ?? 0;
    $date_repeate = Carbon::parse($date_repeate);
    $now = Carbon::now();
    array_push($res, ['repeat_date' => $date_repeate, 'repeate_count' => $count_repeate ++]);
    if($date_repeate->gte($now->addMinute(5)));

    dd($res);
})->group('cek');