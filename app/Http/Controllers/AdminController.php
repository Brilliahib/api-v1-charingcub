<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function createUser(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Validasi role yang diizinkan
        if (!in_array($data['role'], ['daycare', 'nannies', 'user'])) {
            throw new HttpResponseException(
                response(
                    [
                        'statusCode' => 400,
                        'message' => 'Invalid role selected',
                    ],
                    400,
                ),
            );
        }

        if (User::where('email', $data['email'])->exists()) {
            throw new HttpResponseException(
                response(
                    [
                        'statusCode' => 400,
                        'message' => 'Email already in use',
                    ],
                    400,
                ),
            );
        }

        // Membuat user baru dengan role yang dipilih
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'], // Menyimpan role
        ]);


        return response()->json([
            'statusCode' => 201,
            'message' => 'User is successfully created',
            'data' => $user,
        ], 201);
    }

    public function getAllUsers(Request $request): JsonResponse
    {
        $name = $request->query('name', '');

        $users = User::when($name, function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%');
        })->paginate(10);

        return response()->json([
            'statusCode' => 200,
            'message' => 'All users retrieved successfully',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    public function getUserDetail($id): JsonResponse
    {
        $user = User::with(['daycare', 'nannies', 'reviews', 'bookingNannies'])->find($id);

        if (!$user) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'User detail retrieved successfully',
            'data' => $user,
        ], 200);
    }
}
