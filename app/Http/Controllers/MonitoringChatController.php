<?php

namespace App\Http\Controllers;

use App\Models\MonitoringChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MonitoringChatController extends Controller
{
    public function index()
    {
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring chats',
            'data' => MonitoringChat::all(),
        ]);
    }

    public function show($id)
    {
        $chat = MonitoringChat::find($id);
        if (!$chat) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring chat not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring chat',
            'data' => $chat,
        ]);
    }

    public function showByMonitoringChildren($monitoringChildrenId)
    {
        $chats = MonitoringChat::select('id', 'message', 'image', 'created_at', 'updated_at', 'user_id')
            ->with('user:id,name,profile')
            ->where('monitoring_children_id', $monitoringChildrenId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->makeHidden(['user_id']);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved chats by monitoring_children_id',
            'data' => $chats,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:users,id',
            'monitoring_children_id' => 'required|uuid|exists:monitoring_childrens,id',
            'message' => 'required|string',
            'image' => 'nullable|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['user_id', 'monitoring_children_id', 'message']);


        if ($request->hasFile('image')) {
            $originalFileName = $request->file('image')->getClientOriginalName();
            $imageName = time() . '_' . Str::random(5) . '_' . $originalFileName;
            $request->file('image')->storeAs('monitoring/chats', $imageName, 'public');
            $data['image'] = 'public/monitoring/chats/' . $imageName;
        }

        $chat = MonitoringChat::create($data);

        return response()->json([
            'statusCode' => 201,
            'message' => 'Successfully created monitoring chat',
            'data' => $chat,
        ]);
    }

    public function update(Request $request, $id)
    {
        $chat = MonitoringChat::find($id);
        if (!$chat) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring chat not found',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'uuid|exists:users,id',
            'monitoring_children_id' => 'uuid|exists:monitoring_childrens,id',
            'message' => 'string',
            'image' => 'nullable|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['user_id', 'monitoring_children_id', 'message']);

        if ($request->hasFile('image')) {
            $originalFileName = $request->file('image')->getClientOriginalName();
            $imageName = time() . '_' . Str::random(5) . '_' . $originalFileName;
            $request->file('image')->storeAs('monitoring/chats', $imageName, 'public');
            $data['image'] = 'public/monitoring/chats/' . $imageName;
        }

        $chat->update($data);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully updated monitoring chat',
            'data' => $chat,
        ]);
    }

    public function destroy($id)
    {
        $chat = MonitoringChat::find($id);
        if (!$chat) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring chat not found',
                'data' => null,
            ], 404);
        }

        $chat->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully deleted monitoring chat',
            'data' => null,
        ]);
    }
}
