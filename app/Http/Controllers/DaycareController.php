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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DaycareController extends Controller
{
    public function index(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $query = Daycare::with('facilityImages', 'nannies', 'priceLists'); // Tetap menggunakan with untuk relasi

        // Filter berdasarkan lokasi jika ada parameter 'location'
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->input('location') . '%');
        }

        // Perhitungan jarak jika latitude dan longitude diberikan dalam request
        if ($latitude && $longitude) {
            $query
                ->selectRaw(
                    "daycares.*,
                ROUND(
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(latitude))
                    )), 2
                ) AS distance",
                    [$latitude, $longitude, $latitude], // Sisipkan parameter di sini
                )
                ->orderBy('distance', 'asc'); // Urutkan berdasarkan jarak terdekat
        } else {
            $query->select('daycares.*'); // Pastikan tetap memilih semua kolom jika tidak ada latitude/longitude
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
    public function update(Request $request)
    {
        try {
            $user = auth()->user();
            $daycare = $user->daycare; // Pastikan relasi "daycare" sudah ada di model User

            if (!$daycare) {
                return response()->json([
                    'statusCode' => 404,
                    'message' => 'Daycare not found',
                ]);
            }

            $this->authorize('update', $daycare);

            $request->validate([
                'name' => 'required|string|max:255',
                'images' => 'nullable|image',
                'description' => 'nullable|string',
                'opening_hours' => 'required|date_format:H:i',
                'closing_hours' => 'required|date_format:H:i',
                'opening_days' => 'required|string',
                'phone_number' => 'nullable|string|max:20',
                'facility_images' => 'nullable|array',
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
                'price_lists' => 'nullable|array',
                'price_lists.*.age_start' => 'required|string',
                'price_lists.*.age_end' => 'required|string',
                'price_lists.*.name' => 'required|string',
                'price_lists.*.price' => 'required|integer',
            ]);

            if ($request->hasFile('images')) {
                $imageName = time() . '_' . $request->file('images')->getClientOriginalName();
                $imagePath = $request->file('images')->storeAs('daycare', $imageName, 'public');
                $daycare->images = 'public/' . $imagePath;
            }

            $daycare->update($request->only([
                'name',
                'description',
                'opening_hours',
                'closing_hours',
                'opening_days',
                'phone_number',
                'location',
                'longitude',
                'latitude',
                'location_tracking',
                'price_half',
                'price_full',
                'is_disability',
                'address',
                'bank_account',
                'bank_account_number',
                'bank_account_name'
            ]));

            if ($request->hasFile('facility_images')) {
                FacilityDaycareImage::where('daycare_id', $daycare->id)->delete();

                foreach ($request->file('facility_images') as $facilityImage) {
                    $facilityImageName = time() . '_' . $facilityImage->getClientOriginalName();
                    $facilityImagePath = $facilityImage->storeAs('daycare/facility', $facilityImageName, 'public');

                    FacilityDaycareImage::create([
                        'daycare_id' => $daycare->id,
                        'image_url' => 'public/' . $facilityImagePath,
                    ]);
                }
            }

            if ($request->filled('price_lists')) {
                DaycarePriceList::where('daycare_id', $daycare->id)->delete();

                foreach ($request->price_lists as $priceList) {
                    DaycarePriceList::create([
                        'daycare_id' => $daycare->id,
                        'age_start' => $priceList['age_start'],
                        'age_end' => $priceList['age_end'],
                        'name' => $priceList['name'],
                        'price' => $priceList['price'],
                    ]);
                }
            }

            return response()->json([
                'statusCode' => 200,
                'message' => 'Daycare successfully updated',
                'data' => $daycare,
            ]);
        } catch (\Exception $e) {
            Log::error('Error occurred during update:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'statusCode' => 500,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
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

        $nannies = $daycare->nannies()->with('user')->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nannies for the daycare',
            'data' => $nannies,
        ]);
    }

    // get my daycare
    public function getMyDaycare(): JsonResponse
    {
        $userId = auth()->id();

        $daycare = Daycare::with(['facilityImages', 'priceLists'])->where('user_id', $userId)->first();

        if (!$daycare) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Daycare tidak ditemukan untuk user ini',
            ], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Berhasil mengambil data daycare',
            'data' => $daycare,
        ]);
    }
}
