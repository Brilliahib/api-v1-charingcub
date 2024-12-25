<?php

namespace App\Http\Controllers;

use App\Models\BookingDaycare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingDaycareController extends Controller
{
    // Book a daycare
    public function bookDaycare(Request $request)
    {
        $request->validate([
            'daycare_id' => 'required|exists:daycares,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'name_babies' => 'required|string',
            'age_babies' => 'required',
            'special_request' => 'nullable|string',
        ]);

        $userId = auth()->id();

        $booking = BookingDaycare::create([
            'user_id' => $userId,
            'daycare_id' => $request->daycare_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'name_babies' => $request->name_babies,
            'age_babies' => $request->age_babies,
            'special_request' => $request->special_request,
        ]);

        return response()->json(
            [
                'statusCode' => 201,
                'message' => 'Daycare booked successfully.',
                'data' => $booking,
            ],
            201,
        );
    }

    // Approve a daycare booking (Admin or Daycare Staff)
    public function approveBooking($id)
    {
        $booking = BookingDaycare::findOrFail($id);
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

    // Upload payment proof for daycare booking
    public function uploadPaymentProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $booking = BookingDaycare::findOrFail($id);

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

        if ($request->hasFile('payment_proof')) {
            $originalFileName = $request->file('payment_proof')->getClientOriginalName();
            $paymentProofName = time() . '_' . $originalFileName;

            $request->file('payment_proof')->storeAs('daycares/payment', $paymentProofName, 'public');

            $booking->payment_proof = 'storage/daycares/payment/' . $paymentProofName;
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
        $booking->is_paid = true;
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
        $bookings = Auth::user()->bookingDaycares()->with('daycare.user')->get();

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
        $booking = BookingDaycare::with(['user', 'daycare.user'])->findOrFail($id);

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
