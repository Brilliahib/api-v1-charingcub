<?php

namespace App\Http\Controllers;

use App\Models\BookingDaycare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingDaycareController extends Controller
{
    public function __construct()
    {
        Xendit::setApiKey(env('XENDIT_API_KEY'));
    }
    
    public function bookDaycare(Request $request)
    {
        // Validasi input
        $request->validate([
            'daycare_id' => 'required|exists:daycares,id',
            'daycare_price_list_id' => 'required|exists:daycare_price_lists,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'name_babies' => 'required|string',
            'age_babies' => 'required',
            'special_request' => 'nullable|string',
        ]);

        $userId = auth()->id();

        // Membuat booking daycare baru
        $booking = BookingDaycare::create([
            'user_id' => $userId,
            'daycare_id' => $request->daycare_id,
            'daycare_price_list_id' => $request->daycare_price_list_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'name_babies' => $request->name_babies,
            'age_babies' => $request->age_babies,
            'special_request' => $request->special_request,
            'payment_status' => 'pending', // Status pembayaran sementara
            'payment_method' => null, // Pembayaran belum dipilih
        ]);

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Booking daycare created successfully.',
                'data' => $booking,
            ],
            201,
        );
    }

    // Approve a daycare booking (Admin or Daycare Staff)
    public function approveBooking($id)
    {
        $booking = BookingDaycare::findOrFail($id);
        $booking->is_approved = 1;
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

    // Upload payment proof for daycare booking
    public function uploadPaymentProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $booking = BookingDaycare::findOrFail($id);

        if ($request->hasFile('payment_proof')) {
            $originalFileName = $request->file('payment_proof')->getClientOriginalName();
            $paymentProofName = time() . '_' . $originalFileName;

            $request->file('payment_proof')->storeAs('daycares/payment', $paymentProofName, 'public');

            $booking->payment_proof = 'public/daycares/payment/' . $paymentProofName;
        }

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

    // Confirm payment for daycare booking
    public function paidConfirmationBooking($id)
    {
        $booking = BookingDaycare::findOrFail($id);
        $booking->is_paid = 1;
        $booking->save();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking payment confirmed successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    // List user bookings
    public function listUserBookings()
    {
        $bookings = Auth::user()->bookingDaycares()->with('daycares')->get();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'User bookings retrieved successfully.',
                'data' => $bookings,
            ],
            200,
        );
    }

    // Get booking details for a user
    public function getUserBookingDetail($id)
    {
        $booking = BookingDaycare::with(['user', 'daycares.user'])->findOrFail($id);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking details retrieved successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    // List daycare bookings
    public function listDaycareBookings()
    {
        $user = Auth::user();

        $daycareProfile = $user->daycare;

        $bookings = BookingDaycare::where('daycare_id', $daycareProfile->id)
            ->with('user')
            ->get();

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Daycare bookings retrieved successfully.',
                'data' => $bookings,
            ],
            200,
        );
    }
}
