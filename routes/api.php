<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaycareController;
use App\Http\Controllers\NannyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/get-auth', [AuthController::class, 'getAuth']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/update-account', [AuthController::class, 'updateAccount']);

    Route::post('/daycare/review', [DaycareController::class, 'reviewDaycare']);

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        // Rute untuk Article
        Route::prefix('article')->group(function () {
            Route::post('/', [ArticleController::class, 'create']);
        });

        // Rute untuk ArticleType
        Route::prefix('article-types')->group(function () {
            Route::post('/', [ArticleTypeController::class, 'create']); // Create a new article type
        });

        // Route untuk Daycare
        Route::prefix('daycares')->group(function () {
            Route::post('/', [DaycareController::class, 'store']); // Create a new daycare
            Route::put('/{id}', [DaycareController::class, 'update']); // Update an existing daycare
            Route::delete('/{id}', [DaycareController::class, 'destroy']); // Delete a daycare
        });

        // Route untuk Daycare
        Route::prefix('nannies')->group(function () {
            Route::post('/', [NannyController::class, 'store']); // Create a new daycare
            Route::put('/{id}', [NannyController::class, 'update']); // Update an existing daycare
            Route::delete('/{id}', [NannyController::class, 'destroy']); // Delete a daycare
        });
    });

    // Daycare-only routes
    Route::middleware('role:daycare')->group(function () {
        // Route untuk Daycare
        Route::prefix('daycares')->group(function () {
            Route::post('/', [DaycareController::class, 'store']); // Create a new daycare
            Route::put('/{id}', [DaycareController::class, 'update']); // Update an existing daycare
            Route::delete('/{id}', [DaycareController::class, 'destroy']); // Delete a daycare
        });
    });

    Route::middleware('role:nannies')->group(function () {
        // Route untuk Daycare
        Route::prefix('nannies')->group(function () {
            Route::post('/', [NannyController::class, 'store']); // Create a new daycare
            Route::put('/{id}', [NannyController::class, 'update']); // Update an existing daycare
            Route::delete('/{id}', [NannyController::class, 'destroy']); // Delete a daycare
        });
    });
});

// Route for not auth
Route::get('/article/{id}', [ArticleController::class, 'show']); // Get a single article by ID
Route::get('/article', [ArticleController::class, 'getAllArticle']); // Get all articles

Route::get('/article-types', [ArticleTypeController::class, 'getAllArticleType']); // Get all article types
Route::get('/article-types/{id}', [ArticleController::class, 'showArticleType']); // Get a single article type by ID

// Route untuk mendapatkan semua daycare
Route::get('/daycares', [DaycareController::class, 'index']); // Get all daycares
Route::get('/daycares/{id}', [DaycareController::class, 'show']); // Get a single daycare by ID

Route::get('/nannies', [NannyController::class, 'index']); // Get all daycares
Route::get('/nannies/{id}', [NannyController::class, 'show']); // Get a single daycare by ID
