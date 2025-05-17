<?php

namespace App\Http\Controllers;

use App\Models\MonitoringChildren;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MonitoringChildrenController extends Controller
{
    public function index()
    {
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring data',
            'data' => MonitoringChildren::all(),
        ]);
    }

    public function show($id)
    {
        $data = MonitoringChildren::with(['user:id,name', 'daycare:id,name'])->find($id);
        if (!$data) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring data not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring data',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:users,id',
            'daycare_id' => 'required|uuid|exists:daycares,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = MonitoringChildren::create($request->all());

        return response()->json([
            'statusCode' => 201,
            'message' => 'Successfully created monitoring data',
            'data' => $data,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $data = MonitoringChildren::find($id);
        if (!$data) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring data not found',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'uuid|exists:users,id',
            'daycare_id' => 'uuid|exists:daycares,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        $data->update($request->all());

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully updated monitoring data',
            'data' => $data,
        ]);
    }

    public function destroy($id)
    {
        $data = MonitoringChildren::find($id);
        if (!$data) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Monitoring data not found',
                'data' => null,
            ], 404);
        }

        $data->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully deleted monitoring data',
            'data' => null,
        ]);
    }

    public function showByDaycareId($daycare_id)
    {
        $data = MonitoringChildren::with(['user:id,name', 'daycare:id,name'])
            ->where('daycare_id', $daycare_id)
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring data by daycare ID',
            'data' => $data,
        ]);
    }

    public function showByUserId($user_id)
    {
        $data = MonitoringChildren::with(['user:id,name', 'daycare:id,name'])
            ->where('user_id', $user_id)
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring data by user ID',
            'data' => $data,
        ]);
    }

    // show monitoring child current user
    public function showByCurrentUser()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        $data = MonitoringChildren::with(['user:id,name', 'daycare:id,name'])
            ->where('user_id', $userId)
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved monitoring data for current user',
            'data' => $data,
        ]);
    }
}
