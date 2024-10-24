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
}
