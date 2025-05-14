<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingDaycareController;
use App\Http\Controllers\BookingNanniesController;
use App\Http\Controllers\ChatRoomController;
use App\Http\Controllers\DaycareController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NannyController;
use App\Http\Controllers\TalkController;
use App\Models\BookingDaycare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/get-auth', [AuthController::class, 'getAuth']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/update-account', [AuthController::class, 'updateAccount']);
    Route::post('/daycares/review', [DaycareController::class, 'reviewDaycare']);

    Route::post('/talk', [TalkController::class, 'store']);

    Route::get('/nannies', [BookingNanniesController::class, 'listNannies']);
    Route::post('/nannies/booking', [BookingNanniesController::class, 'bookNanny']);
    Route::post('/nannies/booking/{id}/payment', [BookingNanniesController::class, 'uploadPaymentProof']);
    Route::get('/users/nannies/booking/list', [BookingNanniesController::class, 'listUserBookings']);
    Route::get('/users/nannies/booking/{id}', [BookingNanniesController::class, 'getUserBookingDetail']);

    Route::post('/daycares/booking', [BookingDaycareController::class, 'bookDaycare']);
    Route::post('/daycares/booking/{id}/payment', [BookingDaycareController::class, 'uploadPaymentProof']);
    Route::get('/users/daycares/booking/list', [BookingDaycareController::class, 'listUserBookings']);
    Route::get('/users/daycares/booking/{id}', [BookingDaycareController::class, 'getUserBookingDetail']);

    Route::get('/monitoring/daycares', [BookingDaycareController::class, 'listUserPaidDaycares']);

    Route::post('/feedback', [FeedbackController::class, 'store']);

    Route::middleware('role:admin')->group(function () {
        Route::prefix('user')->group(function () {
            Route::post('/', [AdminController::class, 'createUser']);
            Route::get('/', [AdminController::class, 'getAllUsers']);
            Route::get('/{id}', [AdminController::class, 'getUserDetail']);
        });

        Route::get('/feedback', [FeedbackController::class, 'index']);
        Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
        Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
        Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);

        Route::prefix('article')->group(function () {
            Route::post('/', [ArticleController::class, 'create']);
            Route::put('/{id}', [ArticleController::class, 'update']);
            Route::delete('/{id}', [ArticleController::class, 'destroy']);
        });

        Route::prefix('user')->group(function () {
            Route::post('/', [AdminController::class, 'createUser']);
        });

        Route::prefix('article-types')->group(function () {
            Route::post('/', [ArticleTypeController::class, 'create']);
            Route::put('{id}', [ArticleTypeController::class, 'update']);
            Route::delete('{id}', [ArticleTypeController::class, 'destroy']);
        });

        Route::prefix('daycares')->group(function () {
            Route::post('/', [DaycareController::class, 'store']);
            Route::put('/{id}', [DaycareController::class, 'update']);
            Route::delete('/{id}', [DaycareController::class, 'destroy']);
            Route::post('/booking/{id}/approve', [BookingDaycareController::class, 'approveBooking']);
            Route::post('/booking/{id}/paid', [BookingDaycareController::class, 'paidConfirmationBooking']);
        });

        Route::prefix('nannies')->group(function () {
            Route::post('/', [NannyController::class, 'store']);
            Route::put('/{id}', [NannyController::class, 'update']);
            Route::delete('/{id}', [NannyController::class, 'destroy']);
            Route::post('/booking/{id}/approve', [BookingNanniesController::class, 'approveBooking']);
            Route::post('/booking/{id}/paid', [BookingNanniesController::class, 'paidConfirmationBooking']);
        });
    });

    Route::middleware('role:daycare')->group(function () {
        Route::prefix('daycares')->group(function () {
            Route::get('/profile', [DaycareController::class, 'getUserDaycare']);
            Route::post('/', [DaycareController::class, 'store']);
            Route::put('/{id}', [DaycareController::class, 'update']);
            Route::delete('/{id}', [DaycareController::class, 'destroy']);
            Route::post('/booking/{id}/approve', [BookingDaycareController::class, 'approveBooking']);
            Route::post('/booking/{id}/paid', [BookingDaycareController::class, 'paidConfirmationBooking']);
            Route::get('/nannies/list', [DaycareController::class, 'getNanniesByDaycareId']);
            Route::get('/booking/list', [BookingDaycareController::class, 'listDaycareBookings']);
            Route::get('/income-summary', [BookingDaycareController::class, 'getDaycareIncomeSummary']);
            Route::get('/income-total', [BookingDaycareController::class, 'getDaycareIncomeTotal']);
            Route::get('/income-today', [BookingDaycareController::class, 'getDaycareIncomeToday']);
        });

        Route::prefix('user')->group(function () {
            Route::post('/nannies', [DaycareController::class, 'createNannies']);
        });
    });

    Route::middleware('role:nannies')->group(function () {
        Route::prefix('nannies')->group(function () {
            Route::get('/profile', [NannyController::class, 'getUserNanny']);
            Route::post('/', [NannyController::class, 'store']);
            Route::put('/{id}', [NannyController::class, 'update']);
            Route::delete('/{id}', [NannyController::class, 'destroy']);
            Route::post('/booking/{id}/approve', [BookingNanniesController::class, 'approveBooking']);
            Route::post('/booking/{id}/paid', [BookingNanniesController::class, 'paidConfirmationBooking']);
            Route::get('/booking/list', [BookingNanniesController::class, 'listNannyBookings']);
        });
    });

    Route::middleware('role:psychiatrist')->group(function () {
        Route::prefix('talk')->group(function () {
            Route::post('/answer/question', [TalkController::class, 'createTalkAnswer']);
        });
    });
});

Route::get('/article/{id}', [ArticleController::class, 'show']);
Route::get('/article', [ArticleController::class, 'getAllArticle']);

Route::get('/article-types', [ArticleTypeController::class, 'getAllArticleType']);
Route::get('/article-types/{id}', [ArticleTypeController::class, 'showArticleType']);

Route::get('/daycares', [DaycareController::class, 'index']);
Route::get('/daycares/disability', [DaycareController::class, 'getAllWithDisability']);
Route::get('/daycares/{id}', [DaycareController::class, 'show']);

Route::get('/nannies', [NannyController::class, 'index']);
Route::get('/nannies/daycare/{daycare_id}', [NannyController::class, 'getNanniesByDaycare']);
Route::get('/nannies/{id}', [NannyController::class, 'show']);

Route::get('/talk', [TalkController::class, 'index']);
Route::get('/talk/{id}', [TalkController::class, 'show']);

Route::post('/midtrans/payment/notification', [BookingDaycareController::class, 'handleMidtransNotification']);
