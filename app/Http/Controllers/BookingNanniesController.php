<?php

namespace App\Http\Controllers;

use App\Models\BookingNannies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingNanniesController extends Controller
{
    // Book a nanny
    public function bookNanny(Request $request)
    {
        $request->validate([
            'nanny_id' => 'required|exists:nannies,id',
            'price_id' => 'required|exists:nanny_price_lists,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'name_babies' => 'required|string',
            'age_babies' => 'required|integer',
            'special_request' => 'nullable|string',
        ]);

        $userId = auth()->id();

        $booking = BookingNannies::create([
            'user_id' => $userId,
            'nanny_id' => $request->nanny_id,
            'price_id' => $request->price_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'name_babies' => $request->name_babies,
            'age_babies' => $request->age_babies,
            'special_request' => $request->special_request,
        ]);

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Nanny booked successfully.',
                'data' => $booking,
            ],
            201,
        );
    }

    // Approve a booking (Admin or Nanny)
    public function approveBooking($id)
    {
        $booking = BookingNannies::findOrFail($id);
        $booking->is_approved = true;
        $booking->save();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking approved successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    // Upload payment proof
    public function uploadPaymentProof(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Cari booking berdasarkan ID
        $booking = BookingNannies::findOrFail($id);

        // Cek apakah booking sudah di-approve
        if (!$booking->is_approved) {
            return response()->json(
                [
                    'statusCode' => 403,
                    'message' => 'Booking has not been approved yet.',
                    'data' => null,
                ],
                403,
            );
        }

        // Cek apakah file payment proof ada
        if ($request->hasFile('payment_proof')) {
            $originalFileName = $request->file('payment_proof')->getClientOriginalName();
            $paymentProofName = time() . '_' . $originalFileName;

            // Simpan file ke storage
            $request->file('payment_proof')->storeAs('nannies/payment', $paymentProofName, 'public');

            // Simpan path payment_proof ke database
            $booking->payment_proof = 'public/nannies/payment/' . $paymentProofName;
        }

        // Simpan perubahan ke database
        $booking->save();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Payment proof uploaded successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    // Approve a booking (Admin or Nanny)
    public function paidConfirmationBooking($id)
    {
        $booking = BookingNannies::findOrFail($id);
        $booking->is_paid = true;
        $booking->save();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking approved successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    public function listUserBookings(Request $request)
    {
        $name = $request->query('name', '');
        $nannyId = $request->query('nanny_id', '');

        $bookings = Auth::user()
            ->bookingNannies()
            ->when($name, function ($query, $name) {
                $query->where('name_babies', 'like', '%' . $name . '%');
            })
            ->when($nannyId, function ($query, $nannyId) {
                $query->where('nanny_id', $nannyId);
            })
            ->with('nannies.user')
            ->paginate(10);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'User bookings retrieved successfully.',
                'data' => $bookings->items(),
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                    'last_page' => $bookings->lastPage(),
                ],
            ],
            200,
        );
    }

    public function getUserBookingDetail($id)
    {
        $booking = BookingNannies::with(['user', 'nannies.user'])->findOrFail($id);

        if ($booking->user_id !== auth()->id()) {
            return response()->json(
                [
                    'statusCode' => 403,
                    'message' => 'You are not authorized to view this booking.',
                    'data' => null,
                ],
                403,
            );
        }

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking details retrieved successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    public function listNannyBookings(Request $request)
    {
        $user = Auth::user();

        $nannyProfile = $user->nannies;

        $name = $request->query('name', '');

        $bookings = BookingNannies::where('nanny_id', $nannyProfile->id)
            ->when($name, function ($query, $name) {
                $query->where('name_babies', 'like', '%' . $name . '%')->orWhereHas('user', function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                });
            })
            ->with('user')
            ->paginate(10);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Nanny bookings retrieved successfully.',
                'data' => $bookings->items(),
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                    'last_page' => $bookings->lastPage(),
                ],
            ],
            200,
        );
    }
}
