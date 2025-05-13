<?php

namespace App\Http\Controllers;

use App\Models\BookingDaycare;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;

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
            'price_id' => 'required|exists:daycare_price_lists,id',
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
            'price_id' => $request->price_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'name_babies' => $request->name_babies,
            'age_babies' => $request->age_babies,
            'special_request' => $request->special_request,
            'payment_status' => 'pending',
        ]);

        $orderId = 'booking-' . $booking->id;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $booking->priceLists->price,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'item_details' => [
                [
                    'id' => $booking->id,
                    'price' => $booking->priceLists->price,
                    'quantity' => 1,
                    'name' => 'Daycare Booking: ' . $booking->id,
                ],
            ],
            'callbacks' => [
                'finish' => 'https://charingcub.com/dashboard/bookings/daycares',
                'unfinish' => 'https://charingcub.com/dashboard/bookings/daycares',
                'error' => 'https://charingcub.com/dashboard/bookings/daycares',
            ],
        ];

        try {
            $snapResponse = \Midtrans\Snap::createTransaction($params);

            return response()->json(
                [
                    'statusCode' => 201,
                    'message' => 'Daycare booked successfully. Payment URL generated.',
                    'data' => [
                        'booking' => $booking,
                        'payment_url' => $snapResponse->redirect_url,
                    ],
                ],
                201,
            );
        } catch (\Exception $e) {
            Log::error('Booking Daycare Error: ' . $e->getMessage());
            return response()->json(
                [
                    'statusCode' => 500,
                    'message' => 'Failed to create payment transaction.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function handleMidtransNotification(Request $request)
    {
        $notif = new \Midtrans\Notification();

        $transactionStatus = $notif->transaction_status;
        $orderId = substr($notif->order_id, 8);
        $paymentType = $notif->payment_type;

        $booking = BookingDaycare::findOrFail($orderId);

        if ($transactionStatus == 'capture') {
            $booking->payment_status = 'paid';
        } elseif ($transactionStatus == 'pending') {
            $booking->payment_status = 'pending';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $booking->payment_status = 'cancelled';
        }

        $booking->payment_method = $paymentType;
        $booking->save();

        return response()->json(['message' => 'Notification processed successfully.'], 200);
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
    public function listUserBookings(Request $request)
    {
        $name = $request->query('name', '');
        $daycareId = $request->query('daycare_id', '');

        $bookings = Auth::user()
            ->bookingDaycares()
            ->when($name, function ($query, $name) {
                $query->where('name_babies', 'like', '%' . $name . '%');
            })
            ->when($daycareId, function ($query, $daycareId) {
                $query->where('daycare_id', $daycareId);
            })
            ->with('daycares', 'daycares')
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

    // Get booking details for a user
    public function getUserBookingDetail($id)
    {
        $booking = BookingDaycare::with(['user', 'daycares', 'priceLists'])->findOrFail($id);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Booking details retrieved successfully.',
                'data' => $booking,
            ],
            200,
        );
    }

    public function listDaycareBookings(Request $request)
    {
        $user = Auth::user();

        $daycareProfile = $user->daycare;
        if (!$daycareProfile) {
            return response()->json(
                [
                    'statusCode' => 404,
                    'message' => 'User does not have a daycare profile.',
                    'data' => [],
                ],
                404,
            );
        }

        $name = $request->query('name', '');

        $bookings = BookingDaycare::where('daycare_id', $daycareProfile->id)
            ->when($name, function ($query, $name) {
                $query->where('name_babies', 'like', '%' . $name . '%')
                    ->orWhereHas('user', function ($query) use ($name) {
                        $query->where('name', 'like', '%' . $name . '%');
                    });
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(
            [
                'statusCode' => 200,
                'message' => 'Daycare bookings retrieved successfully.',
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

    public function getDaycareIncomeSummary(Request $request)
    {
        $user = Auth::user();
        $daycareProfile = $user->daycare;

        if (!$daycareProfile) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User does not have a daycare profile.',
                'data' => [],
            ], 404);
        }

        $range = $request->query('range', 'weekly'); // weekly | monthly | yearly
        $endDate = Carbon::now();
        $startDate = match ($range) {
            'monthly' => $endDate->copy()->subMonth(),
            'yearly' => $endDate->copy()->subYear(),
            default => $endDate->copy()->subWeek(),
        };

        // Total income
        $totalIncome = DB::table('booking_daycares')
            ->where('daycare_id', $daycareProfile->id)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_payment');

        // Daily income
        $dailyIncome = DB::table('booking_daycares')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_payment) as total')
            )
            ->where('daycare_id', $daycareProfile->id)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Income summary retrieved successfully.',
            'data' => [
                'total_income' => $totalIncome,
                'daily_income' => $dailyIncome,
                'range' => $range,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ], 200);
    }

    public function getDaycareIncomeTotal()
    {
        $user = Auth::user();
        $daycareProfile = $user->daycare;

        if (!$daycareProfile) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User does not have a daycare profile.',
                'data' => [],
            ], 404);
        }

        // Total income berdasarkan join ke tabel price list
        $totalIncome = DB::table('booking_daycares')
            ->join('daycare_price_lists', 'booking_daycares.price_id', '=', 'daycare_price_lists.id')
            ->where('booking_daycares.daycare_id', $daycareProfile->id)
            ->where('booking_daycares.payment_status', 'paid')
            ->sum('daycare_price_lists.price');

        // Daily income berdasarkan tanggal booking (created_at)
        $dailyIncome = DB::table('booking_daycares')
            ->join('daycare_price_lists', 'booking_daycares.price_id', '=', 'daycare_price_lists.id')
            ->select(
                DB::raw('DATE(booking_daycares.created_at) as date'),
                DB::raw('SUM(daycare_price_lists.price) as total')
            )
            ->where('booking_daycares.daycare_id', $daycareProfile->id)
            ->where('booking_daycares.payment_status', 'paid')
            ->groupBy(DB::raw('DATE(booking_daycares.created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Total daycare income retrieved successfully.',
            'data' => [
                'total_income' => $totalIncome,
                'daily_income' => $dailyIncome,
            ],
        ], 200);
    }

    public function getDaycareIncomeToday()
    {
        $user = Auth::user();
        $daycareProfile = $user->daycare;

        if (!$daycareProfile) {
            return response()->json([
                'statusCode' => 404,
                'message' => 'User does not have a daycare profile.',
                'data' => [],
            ], 404);
        }

        $today = Carbon::today(); // ambil tanggal hari ini

        // Hitung total income hari ini
        $totalIncomeToday = DB::table('booking_daycares')
            ->join('daycare_price_lists', 'booking_daycares.price_id', '=', 'daycare_price_lists.id')
            ->where('booking_daycares.daycare_id', $daycareProfile->id)
            ->where('booking_daycares.payment_status', 'paid')
            ->whereDate('booking_daycares.created_at', $today)
            ->sum('daycare_price_lists.price');

        return response()->json([
            'statusCode' => 200,
            'message' => 'Today\'s daycare income retrieved successfully.',
            'data' => [
                'total_income_today' => $totalIncomeToday,
                'date' => $today->toDateString(),
            ],
        ], 200);
    }

    public function listUserPaidDaycares(Request $request)
    {
        $daycareNames = Auth::user()
            ->bookingDaycares()
            ->where('payment_status', 'paid')
            ->with('daycares:id,name')
            ->get()
            ->map(fn($booking) => $booking->daycares?->name)
            ->filter() // remove null values
            ->unique()
            ->values();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Paid daycare names retrieved successfully.',
            'data' => $daycareNames,
        ]);
    }
}
