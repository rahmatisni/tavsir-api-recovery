<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ChatRequest;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\TransOrder;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $chats = Chat::all();
        return response()->json(ChatResource::collection($chats));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ChatRequest $request)
    {
        $chat = [
            'user_id' => auth()->user()->id ?? $request->user_id,
            'user_name' => auth()->user()->name ?? $request->user_name,
            'text'  => $request->text ?? '-',
            'date' => date('Y-m-d H:i:s'),
        ];

        $order = Chat::where('trans_order_id', $request->trans_order_id)->first();
        if ($order) {
            $oldChat = $order->chat;
            array_push($oldChat, $chat);
            $order->update([
                'chat' => $oldChat
            ]);
            $Chat = $order;
        } else {
            $Chat = Chat::create([
                'trans_order_id' => $request->trans_order_id,
                'chat' => array($chat)
            ]);
        }

        return response()->json(new ChatResource($Chat));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(TransOrder $chat)
    {
        $record = Chat::where('trans_order_id', $chat->id)->first();
        return response()->json(new ChatResource($record));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
