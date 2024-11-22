<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatRoomController extends Controller
{
    public function getAllChatRooms()
{
    $userId = auth()->id();

    $chatRooms = ChatRoom::with(['userOne', 'userTwo', 'messages'])
        ->where('user_one_id', $userId)
        ->orWhere('user_two_id', $userId)
        ->get()
        ->map(function ($chatRoom) use ($userId) {
            $opponent = $chatRoom->user_one_id === $userId ? $chatRoom->userTwo : $chatRoom->userOne;
            $lastMessage = $chatRoom->messages()->latest()->first(); // Ambil pesan terakhir

            return [
                'id' => $chatRoom->id,
                'opponent' => [
                    'id' => $opponent->id,
                    'name' => $opponent->name,
                    'email' => $opponent->email,
                    'role' => $opponent->role,
                ],
                'last_chat' => $lastMessage ? $lastMessage->message : null, // Mengambil isi pesan terakhir
                'last_chat_created_at' => $lastMessage ? $lastMessage->created_at : null, // Mengambil waktu pembuatan pesan terakhir
                'created_at' => $chatRoom->created_at,
                'updated_at' => $chatRoom->updated_at,
            ];
        });

    return response()->json([
        'statusCode' => 200,
        'message' => 'Chat rooms retrieved successfully',
        'data' => $chatRooms,
    ]);
}


    public function createChatRoom(Request $request)
    {
        $validated = $request->validate([
            'user_two_id' => 'required|exists:users,id',
        ]);

        $userOneId = auth()->id();
        $userTwoId = $validated['user_two_id'];

        $chatRoom = ChatRoom::where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)->where('user_two_id', $userTwoId);
        })
            ->orWhere(function ($query) use ($userOneId, $userTwoId) {
                $query->where('user_one_id', $userTwoId)->where('user_two_id', $userOneId);
            })
            ->first();

        if ($chatRoom) {
            return response()->json([
                'statusCode' => 200,
                'message' => 'Chat room already exists',
                'data' => $chatRoom,
            ]);
        }

        $chatRoom = ChatRoom::create([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);

        return response()->json([
            'statusCode' => 201,
            'message' => 'Chat room created successfully',
            'data' => $chatRoom,
        ]);
    }
}
