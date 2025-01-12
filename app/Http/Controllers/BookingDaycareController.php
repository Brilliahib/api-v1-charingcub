<?php

namespace App\Http\Controllers;

use App\Models\BookingDaycare;
use App\Models\DaycarePriceList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\CoreApi;

class BookingDaycareController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function bookDaycare(Request $request)
    {
        $request->validate([
            'daycare_id' => 'required|exists:daycares,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'name_babies' => 'required|string',
            'age_babies' => 'required',
            'special_request' => 'nullable|string',
            'price' => 'required',
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
            'price' => $request->price,
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

    public function payWithOvo(Request $request, $id)
    {
        $request->validate([
            'ovo_number' => 'required|numeric',
        ]);

        $booking = BookingDaycare::findOrFail($id);

        $params = [
            'transaction_details' => [
                'order_id' => 'OVO-' . $booking->id,
                'gross_amount' => $booking->price,
            ],
            'payment_type' => 'gopay',
            'gopay' => [
                'enable_callback' => false,
                'callback_url' => 'https://your-callback-url.com',
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email' => $booking->user->email,
                'phone' => $request->ovo_number,
            ],
        ];

        $response = CoreApi::charge($params);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment request created successfully',
            'data' => $response,
        ]);
    }

    // Generate Payment for QRIS
    public function payWithQris($id)
    {
        $booking = BookingDaycare::findOrFail($id);

        $params = [
            'transaction_details' => [
                'order_id' => 'QRIS-' . $booking->id,
                'gross_amount' => $booking->price,
            ],
            'payment_type' => 'gopay',
            'gopay' => [
                'enable_callback' => false,
                'callback_url' => 'https://your-callback-url.com',
            ],
        ];

        $response = CoreApi::charge($params);

        $qrCodeString = '';
        if (isset($response->actions)) {
            foreach ($response->actions as $action) {
                if ($action->name === 'generate-qr-code') {
                    $qrCodeString = $action->url;
                    break;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'QRIS payment link generated successfully',
            'qr_code_string' => $qrCodeString,
            'data' => $response,
        ]);
    }

    // Generate Payment for Dana
    public function payWithDana($id)
    {
        $booking = BookingDaycare::findOrFail($id);

        $params = [
            'transaction_details' => [
                'order_id' => 'DANA-' . $booking->id,
                'gross_amount' => $booking->price,
            ],
            'payment_type' => 'echannel', 
            'echannel' => [
            'bill_info' => 'Booking Daycare', 
            'bill_key' => 'BOOK-' . $booking->id,      
        ],
        ];

        $response = CoreApi::charge($params);

        return response()->json([
            'status' => 'success',
            'message' => 'Dana payment created successfully',
            'data' => $response,
        ]);
    }

    // Generate Payment for ShopeePay
    public function payWithShopeePay($id)
    {
        $booking = BookingDaycare::findOrFail($id);

        $params = [
            'transaction_details' => [
                'order_id' => 'SHOPEEPAY-' . $booking->id,
                'gross_amount' => $booking->price,
            ],
            'payment_type' => 'shopeepay',
            'shopeepay' => [
                'callback_url' => 'https://your-callback-url.com',
            ],
        ];

        $response = CoreApi::charge($params);

        return response()->json([
            'status' => 'success',
            'message' => 'ShopeePay payment created successfully',
            'data' => $response,
        ]);
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

    // List user bookings
    public function listUserBookings()
    {
        $bookings = Auth::user()->bookingDaycares()->with('daycares', 'daycares.priceLists')->get();

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
