<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserExport implements FromView
{
    public function __construct(protected $data)
    {
    }

    public function view(): View
    {
        return view('exports.user', ['record' => $this->data]);
    }
}
