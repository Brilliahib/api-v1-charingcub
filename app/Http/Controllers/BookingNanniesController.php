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
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'name_babies' => 'required|string',
            'age_babies' => 'required',
            'special_request' => 'required|nullable',
        ]);

        $userId = auth()->id();

        $booking = BookingNannies::create([
            'user_id' => $userId,
            'nanny_id' => $request->nanny_id,
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
            $booking->payment_proof = 'storage/nannies/payment/' . $paymentProofName;
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

    public function listUserBookings()
    {
        $bookings = Auth::user()->bookingNannies()->get();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'User bookings retrieved successfully.',
                'data' => $bookings,
            ],
            200,
        );
    }

    public function listNannyBookings()
    {
        $nannyId = Auth::id();

        $bookings = BookingNannies::where('nanny_id', $nannyId)->get();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Nanny bookings retrieved successfully.',
                'data' => $bookings,
            ],
            200,
        );
    }
}
