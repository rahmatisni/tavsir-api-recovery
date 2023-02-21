<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    public function auth()
    {
        $res = Http::withOptions(['verify' => false,])->get(env('KIOSBANK_URL'));
        $diges = $res->header('WWW-Authenticate');

        return $diges;
    }

}