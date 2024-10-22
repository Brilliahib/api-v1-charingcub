<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleRequest;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    // Create a new article
    public function create(CreateArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Dapatkan nama file asli
            $originalFileName = $request->file('image')->getClientOriginalName();
            // Buat nama file baru
            $imageName = time() . '_' . $originalFileName;

            // Simpan file dengan nama baru di folder 'article'
            $request->file('image')->storeAs('article', $imageName, 'public');

            // Tambahkan nama file ke data dengan path 'storage/article/nama_file'
            $data['image'] = 'storage/article/' . $imageName; // Menyimpan path dengan folder
        }

        $article = Article::create($data);

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Article successfully created',
                'data' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'image' => $data['image'], // Menggunakan $data['image'] yang sudah diperbarui
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ],
            ],
            201,
        );
    }

    // Get all articles
    public function getAllArticle(): JsonResponse
    {
        $articles = Article::all();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Articles retrieved successfully',
                'data' => $articles,
            ],
            200,
        );
    }

    // Get a single article by ID
    public function show($id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(
                [
                    'statusCode' => 404,
                    'error' => 'Article not found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Article retrieved successfully',
                'data' => $article,
            ],
            200,
        );
    }
}
