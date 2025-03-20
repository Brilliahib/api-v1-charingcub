<?php

namespace App\Http\Controllers;

use App\Models\Talk;
use App\Models\TalkAnswer;
use Illuminate\Http\Request;

class TalkController extends Controller
{
    public function index()
    {
        $talk = Talk::with('user')->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved talk',
            'data' => $talk,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'nullable|string',
            'question_title' => 'required|string',
            'question_detail' => 'required|string',
        ]);

        $validatedData['user_id'] = $request->user_id ?? auth()->id();

        $question = Talk::create($validatedData);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Successfully create question talk',
                'data' => $question,
            ],
            201,
        );
    }

    public function show($id)
    {
        $talk = Talk::with('user', 'talkAnswers', 'talkAnswers.user')->find($id);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved question detail talk',
            'data' => $talk,
        ]);
    }

    public function createTalkAnswer(Request $request) 
    {
        $validatedData = $request->validate([
            'talk_id' => 'required|uuid|exists:talks,id',
            'user_id' => 'nullable|string',
            'answer' => 'required|string',
        ]);

        $validatedData['user_id'] = $request->user_id ?? auth()->id();

        $question = TalkAnswer::create($validatedData);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Successfully create question talk',
                'data' => $question,
            ],
            201,
        );
    }
}
