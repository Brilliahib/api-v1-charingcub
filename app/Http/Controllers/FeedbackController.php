<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index()
    {
        $daycares = Feedback::with('user')->latest()->get();
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved feedbacks',
            'data' => $daycares,
        ]);
    }

    public function show($id)
    {
        $feedback = Feedback::with('user')->findOrFail($id);
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved feedback',
            'data' => $feedback,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rate' => 'required|in:fantastic,good,fine,not so great,improvements needed',
            'comment' => 'nullable|string',
        ]);

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'rate' => $request->rate,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'statusCode' => 201,
            'message' => 'Feedback successfully created',
            'data' => $feedback,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rate' => 'required|in:fantastic,good,fine,not so great,improvements needed',
            'comment' => 'nullable|string',
        ]);

        $feedback = Feedback::findOrFail($id);
        $feedback->update($request->only(['rate', 'comment']));

        return response()->json([
            'statusCode' => 200,
            'message' => 'Feedback successfully updated',
            'data' => $feedback,
        ]);
    }

    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Feedback successfully deleted',
            'data' => null,
        ]);
    }
}
