<?php

namespace App\Http\Controllers;

use App\Models\Nanny;
use App\Models\NannyPriceList;
use Illuminate\Http\Request;

class NannyController extends Controller
{
    public function index()
    {
        $nannies = Nanny::with('user', 'daycare', 'priceLists')->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nannies',
            'data' => $nannies,
        ]);
    }

    public function getNanniesByDaycare($daycare_id)
    {
        $nannies = Nanny::with('user', 'daycare', 'priceLists')
            ->where('daycare_id', $daycare_id)
            ->get();

        if ($nannies->isEmpty()) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'No nannies found for the given daycare.',
            ]);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nannies for the daycare',
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
            'price_lists' => 'nullable|array',
            'price_lists.*.age_start' => 'nullable|string',
            'price_lists.*.age_end' => 'nullable|string',
            'price_lists.*.name' => 'nullable|string',
            'price_lists.*.price' => 'nullable|integer',
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

        if (!empty($request->price_lists) && is_array($request->price_lists)) {
            foreach ($request->price_lists as $priceList) {
                NannyPriceList::create([
                    'nanny_id' => $nanny->id,
                    'age_start' => $priceList['age_start'] ?? null,
                    'age_end' => $priceList['age_end'] ?? null,
                    'name' => $priceList['name'] ?? null,
                    'price' => $priceList['price'] ?? null,
                ]);
            }
        }

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
        $nanny = Nanny::with('user', 'daycare', 'priceLists')->find($id);

        if (!$nanny) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'Nanny not found',
            ]);
        }

        return response()->json([
            'statusCode' => 200,
            'message' => 'Successfully retrieved nanny',
            'data' => $nanny,
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
