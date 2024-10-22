<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleTypeRequest;
use App\Models\ArticleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleTypeController extends Controller
{
    // Create a new article type
    public function create(CreateArticleTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $articleType = ArticleType::create($data);

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Article Type successfully created',
                'data' => $articleType,
            ],
            201,
        );
    }

    // Get all article types
    public function getAllArticleType(): JsonResponse
    {
        $articleTypes = ArticleType::all();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Article Types retrieved successfully',
                'data' => $articleTypes,
            ],
            200,
        );
    }

    // Get a single article type by ID
    public function showArticleType($id): JsonResponse
    {
        $articleType = ArticleType::find($id);

        if (!$articleType) {
            return response()->json(
                [
                    'statusCode' => 404,
                    'error' => 'Article Type not found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Article Type retrieved successfully',
                'data' => $articleType,
            ],
            200,
        );
    }
}
