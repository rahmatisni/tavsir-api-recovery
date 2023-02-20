<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    public function auth()
    {
        return Http::dd()->withoutVerifying()->get('google.com'.'/auth/Sign-On')->header('WWW-Authenticate');
    }

}