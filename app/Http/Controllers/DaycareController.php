<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNanniesFromDaycareRequest;
use App\Models\Daycare;
use App\Models\FacilityDaycareImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DaycareController extends Controller
{
    // Menampilkan semua daycare
    public function index()
    {
        $daycares = Daycare::with('facilityImages', 'nannies')->get();
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved daycares',
            'data' => $daycares,
        ]);
    }

    public function getAllWithDisability()
    {
        $daycares = Daycare::with('facilityImages', 'nannies')->where('is_disability', 1)->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved daycares with disability',
            'data' => $daycares,
        ]);
    }

    // Menampilkan daycare berdasarkan ID
    public function show($id)
    {
        $daycare = Daycare::with('facilityImages', 'nannies.user', 'reviews.user')->findOrFail($id);
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved daycare',
            'data' => $daycare,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'images' => 'required|image',
            'description' => 'nullable|string',
            'opening_hours' => 'required|date_format:H:i',
            'closing_hours' => 'required|date_format:H:i',
            'opening_days' => 'required|string',
            'phone_number' => 'nullable|string|max:20',
            'facility_images' => 'required|array',
            'facility_images.*' => 'image',
            'location' => 'required|string',
            'location_tracking' => 'required|string',
            'price' => 'required|integer',
            'is_disability' => 'required|boolean',
        ]);

        // Images on daycare
        if ($request->hasFile('images')) {
            $imageName = time() . '_' . $request->file('images')->getClientOriginalName();
            $imagePath = $request->file('images')->storeAs('daycare', $imageName, 'public'); // Menyimpan ke storage/app/public/daycare
            $imageUrl = 'storage/' . $imagePath; // Membuat URL untuk diakses
        }

        $userId = auth()->id();

        // Buat daycare baru
        $daycare = Daycare::create($request->only(['name', 'description', 'opening_hours', 'closing_hours', 'opening_days', 'phone_number', 'location', 'location_tracking', 'price', 'is_disability']) + ['images' => $imageUrl, 'user_id' => $userId]);

        // Simpan gambar fasilitas
        foreach ($request->facility_images as $facilityImage) {
            if ($facilityImage) {
                $facilityImageName = time() . '_' . $facilityImage->getClientOriginalName(); // Buat nama file unik
                $facilityImagePath = $facilityImage->storeAs('daycare/facility', $facilityImageName, 'public'); // Menyimpan ke storage/app/public/daycare/facility
                $facilityImageUrl = 'storage/' . $facilityImagePath; // Membuat URL untuk diakses

                FacilityDaycareImage::create([
                    'daycare_id' => $daycare->id,
                    'image_url' => $facilityImageUrl, // Menyimpan URL gambar fasilitas
                ]);
            }
        }

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Daycare successfully created',
                'data' => $daycare,
            ],
            201,
        );
    }

    // Mengupdate daycare
    public function update(Request $request, $id)
    {
        $daycare = Daycare::findOrFail($id);

        $this->authorize('update', $daycare);

        $request->validate([
            'name' => 'string|max:255',
            'images' => 'array',
            'description' => 'nullable|string',
            'opening_hours' => 'date_format:H:i',
            'closing_hours' => 'date_format:H:i',
            'opening_days' => 'array',
            'phone_number' => 'nullable|string|max:20',
            'location' => 'required|string',
            'location_tracking' => 'required|string',
            'price' => 'required|integer',
            'is_disability' => 'required|boolean',
        ]);

        $daycare->update($request->all());

        return response()->json([
            'statusCode' => 200,
            'message' => 'Daycare successfully updated',
            'data' => $daycare,
        ]);
    }

    // Menghapus daycare
    public function destroy($id)
    {
        $daycare = Daycare::findOrFail($id);

        $this->authorize('delete', $daycare);

        $daycare->delete();
        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Daycare successfully deleted',
            ],
            200,
        );
    }

    // Menambahkan review
    public function reviewDaycare(Request $request)
    {
        $request->validate([
            'daycare_id' => 'required|exists:daycares,id',
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::create([
            'daycare_id' => $request->daycare_id,
            'user_id' => auth()->id(), // Asumsi pengguna sudah terautentikasi
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Mengupdate rating dan jumlah reviewer di daycare
        $daycare = Daycare::find($request->daycare_id);
        $daycare->rating = ($daycare->rating * $daycare->reviewers_count + $request->rating) / ($daycare->reviewers_count + 1);
        $daycare->reviewers_count += 1;
        $daycare->save();

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Review successfully added',
                'data' => $review,
            ],
            201,
        );
    }

    public function createNannies(CreateNanniesFromDaycareRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Validasi role yang diizinkan
        if (!in_array($data['role'], ['nannies'])) {
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

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'User is successfully created',
                'data' => $user,
            ],
            201,
        );
    }

    public function getUserDaycare()
    {
        $user = auth()->user();

        if ($user->role !== 'daycare') {
            return response()->json(
                [
                    'statusCode' => 403,
                    'message' => 'Access denied',
                ],
                403,
            );
        }

        $daycare = Daycare::with('facilityImages', 'nannies')
            ->where('user_id', $user->id)
            ->first();

        if (!$daycare) {
            return response()->json([
                'statusCode' => 200,
                'message' => 'You have not created a daycare profile yet',
                'data' => null,
            ]);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved daycare profile',
            'data' => $daycare,
        ]);
    }

    public function getNanniesByDaycareId()
    {
        $user = auth()->user();

        if ($user->role !== 'daycare') {
            return response()->json(
                [
                    'statusCode' => 403,
                    'message' => 'Access denied',
                ],
                403,
            );
        }

        $daycare = Daycare::where('user_id', $user->id)->first();

        if (!$daycare) {
            return response()->json(
                [
                    'statusCode' => 404,
                    'message' => 'Daycare not found',
                ],
                404,
            );
        }

        $nannies = $daycare->nannies;

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nannies for the daycare',
            'data' => $nannies,
        ]);
    }
}
