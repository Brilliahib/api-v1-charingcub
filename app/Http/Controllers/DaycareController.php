<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNanniesFromDaycareRequest;
use App\Models\Daycare;
use App\Models\DaycarePriceList;
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
    public function index(Request $request)
    {
        $query = Daycare::with('facilityImages', 'nannies', 'priceLists');

        // Memeriksa apakah ada parameter 'location' dalam query string
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->input('location') . '%');
        }

        $daycares = $query->get();

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
        $daycare = Daycare::with('facilityImages', 'nannies.user', 'reviews.user', 'priceLists')->findOrFail($id);
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved daycare',
            'data' => $daycare,
        ]);
    }

    public function store(Request $request)
    {
        try {
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
                'address' => 'required|string',
                'longitude' => 'nullable|numeric',
                'latitude' => 'nullable|numeric',
                'location_tracking' => 'required|string',
                'is_disability' => 'required|boolean',
                'bank_account' => 'required|string',
                'bank_account_number' => 'required|string',
                'bank_account_name' => 'required|string',
                'price_lists' => 'required|array', 
                'price_lists.*.age_start' => 'required|string', 
                'price_lists.*.age_end' => 'required|string', 
                'price_lists.*.name' => 'required|string', 
                'price_lists.*.price' => 'required|integer', 
            ]);

            Log::info('Request validated:', $request->all());

            if ($request->hasFile('images')) {
                $imageName = time() . '_' . $request->file('images')->getClientOriginalName();
                $imagePath = $request->file('images')->storeAs('daycare', $imageName, 'public'); 
                $imageUrl = 'public/' . $imagePath;
            }

            $userId = auth()->id();

            $daycare = Daycare::create($request->only(['name', 'description', 'opening_hours', 'closing_hours', 'opening_days', 'phone_number', 'location', 'longitude', 'latitude', 'location_tracking', 'price', 'is_disability', 'address', 'price_half', 'price_full', 'bank_account', 'bank_account_number', 'bank_account_name']) + ['images' => $imageUrl, 'user_id' => $userId]);

            foreach ($request->facility_images as $facilityImage) {
                if ($facilityImage) {
                    $facilityImageName = time() . '_' . $facilityImage->getClientOriginalName(); 
                    $facilityImagePath = $facilityImage->storeAs('daycare/facility', $facilityImageName, 'public');
                    $facilityImageUrl = 'public/' . $facilityImagePath;

                    FacilityDaycareImage::create([
                        'daycare_id' => $daycare->id,
                        'image_url' => $facilityImageUrl, 
                    ]);
                }
            }

            foreach ($request->price_lists as $priceList) {
                DaycarePriceList::create([
                    'daycare_id' => $daycare->id,
                    'age_start' => $priceList['age_start'],
                    'age_end' => $priceList['age_end'],
                    'name' => $priceList['name'],
                    'price' => $priceList['price'],
                ]);
            }

            return response()->json(
                [
                    'statusCode' => 201,
                    'message' => 'Daycare successfully created',
                    'data' => $daycare,
                ],
                201,
            );
        } catch (\Exception $e) {
            Log::error('Error occurred:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json(
                [
                    'statusCode' => 500,
                    'message' => 'An unexpected error occurred',
                ],
                500,
            );
        }
    }

    // Mengupdate daycare
    public function update(Request $request, $id)
    {
        $daycare = Daycare::findOrFail($id);

        $this->authorize('update', $daycare);

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
            'address' => 'required|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'location_tracking' => 'required|string',
            'price_half' => 'required|integer',
            'price_full' => 'required|integer',
            'is_disability' => 'required|boolean',
            'bank_account' => 'required|string',
            'bank_account_number' => 'required|string',
            'bank_account_name' => 'required|string',
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
