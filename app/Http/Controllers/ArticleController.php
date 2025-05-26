<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $data['image'] = 'public/article/' . $imageName; // Menyimpan path dengan folder
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
                    'slug' => $article->slug,
                    'image' => $data['image'], // Menggunakan $data['image'] yang sudah diperbarui
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ],
            ],
            201,
        );
    }

    // Get all articles
    public function getAllArticle(Request $request): JsonResponse
    {
        $title = $request->query('title', '');
        $articles = Article::when($title, function ($query, $title) {
            $query->where('title', 'like', '%' . $title . '%');
        })
            ->latest()
            ->paginate(10);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Articles retrieved successfully',
                'data' => $articles->items(),
                'pagination' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
            ],
            200,
        );
    }

    // get last 4 articles
    public function getLatestArticles(): JsonResponse
    {
        $latestArticles = Article::latest()->take(4)->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Latest 4 articles retrieved successfully',
            'data' => $latestArticles,
        ], 200);
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

    // Update an article by ID
    public function update(UpdateArticleRequest $request, $id): JsonResponse
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

        $data = $request->validated();

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            // Hapus image lama dari storage jika ada
            if ($article->image) {
                // Menghapus file dari folder 'article'
                Storage::disk('public')->delete($article->image);
            }

            // Upload image baru
            $originalFileName = $request->file('image')->getClientOriginalName();
            $imageName = time() . '_' . $originalFileName;
            $request->file('image')->storeAs('article', $imageName, 'public');

            // Update path image di data
            $data['image'] = 'article/' . $imageName; // Hanya simpan path relatif
        }

        // Update article
        $article->update($data);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Article successfully updated',
                'data' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'image' => isset($data['image']) ? $data['image'] : $article->image,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ],
            ],
            200,
        );
    }

    // Delete an article by ID
    public function destroy($id): JsonResponse
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

        // Hapus image dari storage jika ada
        if ($article->image) {
            // Menghapus file dari folder 'article'
            Storage::disk('public')->delete($article->image);
        }

        // Hapus artikel
        $article->delete();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Article successfully deleted',
            ],
            200,
        );
    }
}
