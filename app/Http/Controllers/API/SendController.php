<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SendRequest;
use App\Models\User;
use App\Models\TransOrder;

class SendController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function mail(SendRequest $request, TransOrder $order)
    {
        $data = [
            'user_name' => $request->user_name,
            'user_email' => $request->user_email,
            'path_image' => $request->path_image,
            'order' => $order,
        ];
        \Mail::to($request->user_email)->send(new \App\Mail\SendMail('Struk', 'struk', $data));

        return response()->json(['status' => 'success'], 200);
    }
}
