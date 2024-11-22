<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function getMessages($chatRoomId)
    {
        $chatRoom = ChatRoom::findOrFail($chatRoomId);

        $messages = Message::where('chat_room_id', $chatRoomId)
            ->with('user') 
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Messages retrieved successfully',
            'data' => $messages,
        ]);
    }

    public function sendMessage(Request $request, $chatRoomId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $chatRoom = ChatRoom::findOrFail($chatRoomId);

        $message = Message::create([
            'chat_room_id' => $chatRoomId,
            'user_id' => auth()->id(), 
            'message' => $validated['message'],
        ]);

        return response()->json([
            'statusCode' => 201,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }
}
