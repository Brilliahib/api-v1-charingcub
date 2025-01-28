<?php

namespace App\Http\Controllers;

use App\Models\Nanny;
use Illuminate\Http\Request;

class NannyController extends Controller
{
    public function index()
    {
        $nannies = Nanny::with('user', 'daycare', 'daycare.priceLists')->get();

        $nannies = $nannies->map(function ($nanny) {
            if ($nanny->daycare && $nanny->daycare->priceLists) {
                $priceHalf = $nanny->daycare->priceLists->price_half ?? $nanny->price_half;
                $priceFull = $nanny->daycare->priceLists->price_full ?? $nanny->price_full;
            } else {
                $priceHalf = $nanny->price_half;
                $priceFull = $nanny->price_full;
            }
    
            return [
                'id' => $nanny->id,
                'user' => $nanny->user,
                'daycare' => $nanny->daycare,
                'images' => $nanny->images,
                'gender' => $nanny->gender,
                'age' => $nanny->age,
                'contact' => $nanny->contact,
                'experience_description' => $nanny->experience_description,
                'price_half' => $priceHalf,
                'price_full' => $priceFull,
            ];
        });
    
        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nannies',
            'data' => $nannies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'daycare_id' => 'nullable|string',
            'images' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Validasi untuk gambar
            'gender' => 'required|string|max:10',
            'age' => 'required|integer|min:18',
            'contact' => 'required|string|max:20',
            'experience_description' => 'required|string',
        ]);

        $imageUrl = null; // Variabel untuk menyimpan URL gambar

        // Simpan gambar utama daycare
        if ($request->hasFile('images')) {
            $imageName = time() . '_' . $request->file('images')->getClientOriginalName();
            $imagePath = $request->file('images')->storeAs('nannies', $imageName, 'public'); // Menyimpan ke storage/app/public/daycare
            $imageUrl = 'public/' . $imagePath; // Membuat URL untuk diakses
        }

        // Buat nanny baru dengan data yang sudah dimodifikasi
        $nanny = Nanny::create(array_merge($request->all(), ['images' => $imageUrl, 'user_id' => auth()->id()]));

        return response()->json([
            'statusCode' => 201,
            'message' => 'Nanny created successfully',
            'data' => $nanny,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $nanny = Nanny::with('user', 'daycare', 'daycare.reviews', 'daycare.priceLists')->find($id);

        if (!$nanny) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Nanny not found',
            ]);
        }

        $reviews = $nanny->daycare->reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'name' => $review->user->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ];
        });

        $nannyData = [
            'id' => $nanny->id,
            'name' => $nanny->user->name,
            'rating' => $nanny->daycare->rating,
            'rating_count' => $nanny->daycare->reviewers_count,
            'images' => $nanny->images,
            'gender' => $nanny->gender,
            'age' => $nanny->age,
            'contact' => $nanny->contact,
            'price_half' => $nanny->price_half,
            'price_full' => $nanny->price_full,
            'experience_description' => $nanny->experience_description,
            'daycare_id' => $nanny->daycare->id,
            'daycare_name' => $nanny->daycare->name,
            'daycare_profile' => $nanny->daycare->images,
            'daycare_location' => $nanny->daycare->location,
            'daycare_latitude' => $nanny->daycare->latitude,
            'daycare_longitude' => $nanny->daycare->longitude,
            'daycare_bank' => $nanny->daycare->bank_account,
            'daycare_bank_name' => $nanny->daycare->bank_account_name,
            'daycare_bank_number' => $nanny->daycare->bank_account_number,
            'created_at' => $nanny->created_at,
            'updated_at' => $nanny->updated_at,
            'reviews' => $reviews, 
        ];

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nanny',
            'data' => $nannyData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $nanny = Nanny::find($id);

        $this->authorize('update', $nanny);

        if (!$nanny) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Nanny not found',
            ]);
        }

        $request->validate([
            'name' => 'string|max:255',
            'images' => 'string',
            'gender' => 'string|max:10',
            'age' => 'integer|min:18',
            'contact' => 'string|max:20',
            'price_half' => 'integer',
            'price_full' => 'integer',
            'experience_description' => 'string',
        ]);

        $nanny->update($request->all());

        return response()->json([
            'statusCode' => 200,
            'message' => 'Nanny updated successfully',
            'data' => $nanny,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $nanny = Nanny::find($id);

        $this->authorize('delete', $nanny);

        if (!$nanny) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Nanny not found',
            ]);
        }

        $nanny->delete();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Nanny deleted successfully',
        ]);
    }

    public function getUserNanny()
    {
        $user = auth()->user();

        if ($user->role !== 'nannies') {
            return response()->json(
                [
                    'statusCode' => 403,
                    'message' => 'Access denied',
                ],
                403,
            );
        }

        $nanny = Nanny::with('daycare')
            ->where('user_id', $user->id)
            ->first();

        if (!$nanny) {
            return response()->json([
                'statusCode' => 200,
                'message' => 'You have not created a nanny profile yet',
                'data' => null
            ]);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nanny profile',
            'data' => $nanny,
        ]);
    }
}
