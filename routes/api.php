<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProspectParentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\PaymentSpController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HandleSubmitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallApiController;
use App\Http\Controllers\ParentsController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SchedulePrgController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\KelasController;



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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Dashboard MRE View
Route::get('/studentsall', [StudentController::class, 'studentall']);
Route::get('prospect', [ProspectParentController::class, 'index']);
Route::get('spcall', [ProspectParentController::class, 'callSP']);
Route::get('prgcall', [ProspectParentController::class, 'callPrg']);
Route::get('interest-call', [ProspectParentController::class, 'callInterest']);
Route::get('count_prospect', [ProspectParentController::class, 'countProspectsWithPaymentTypeOne']);
Route::get('count_pending', [ProspectParentController::class, 'countProspectsPending']);
Route::get('count_expired', [ProspectParentController::class, 'countProspectsExpired']);
Route::get('count_paid', [ProspectParentController::class, 'countProspectsPaid']);
Route::put('/prospects/checkin/{id}', [ProspectParentController::class, 'checkin']);
Route::post('manual', [HandleSubmitController::class, 'handleManualPayment']);
Route::put('/payment/update', [HandleSubmitController::class, 'handleManualPaymentUpdate']);
Route::post('parents', [ParentsController::class, 'store']);

//Prospect Parents Controller
Route::post('prospect', [ProspectParentController::class, 'store']);
Route::get('prospect/{id}', [ProspectParentController::class, 'show']);
Route::put('prospect/{id}', [ProspectParentController::class, 'update']);
Route::put('prospect/{id}', [ProspectParentController::class, 'update']);
Route::delete('prospect/{id}', [ProspectParentController::class, 'destroy']);

//Cabang Controller
Route::get('branches', [BranchController::class, 'index']);
Route::post('branches', [BranchController::class, 'store']);
Route::get('branches/{id}', [BranchController::class, 'show']);
Route::put('branches/{id}', [BranchController::class, 'update']);
Route::delete('branches/{id}', [BranchController::class, 'destroy']);
Route::get('branches_std', [BranchController::class, 'branch_std']);
Route::get('branches_dat', [BranchController::class, 'branch_total']);
Route::get('branches_donut', [BranchController::class, 'topThreeBranches']);
Route::get('branch_rev', [BranchController::class, 'branch_revenue']);
Route::get('branch_top', [BranchController::class, 'branch_revtop']);
Route::get('branch_revm', [BranchController::class, 'branch_revenue_month']);
Route::get('branch_topm', [BranchController::class, 'branch_revtop_month']);

//Students Controller
Route::get('student_month', [StudentController::class, 'student_last_three_months']);

//Program Controller
Route::get('programs', [ProgramController::class, 'index']);
Route::post('programs', [ProgramController::class, 'store']);
Route::get('programs/{id}', [ProgramController::class, 'show']);
Route::put('programs/{id}', [ProgramController::class, 'update']);
Route::delete('programs/{id}', [ProgramController::class, 'destroy']);

//PaymentSp Controller
Route::get('payment-sps', [PaymentSpController::class, 'index']);
Route::post('payment-sps', [PaymentSpController::class, 'store']);
Route::get('payment-sps/{id}', [PaymentSpController::class, 'show']);
Route::put('payment-sps/{id}', [PaymentSpController::class, 'update']);
Route::delete('payment-sps/{id}', [PaymentSpController::class, 'destroy']);
Route::post('create-invoice', [PaymentSpController::class, 'createInvoice'])->name('api.create-invoice');;
Route::post('xendit-callback', [PaymentSpController::class, 'handleXenditCallback']);
Route::get('payment_month', [PaymentSpController::class, 'payment_last_three_months']);


//Invitonal Controller
Route::prefix('voucher')->group(function () {
    Route::post('/', [InviteController::class, 'create']);
    Route::get('/', [InviteController::class, 'index']);
    Route::get('/{id}', [InviteController::class, 'show']);
    Route::put('/{id}', [InviteController::class, 'update']);
    Route::delete('/{id}', [InviteController::class, 'delete']);
});

//Messages Controller
Route::apiResource('messages', MessageController::class);
Route::get('messages', [MessageController::class, 'index'])->name('api.messages.index');

//Invite Controller
Route::post('check-invitional-code', [InviteController::class, 'checkInvitationalCode'])->name('api.check-invitional-code');

//Send Message Controller
Route::post('send-message', [WhatsAppController::class, 'sendMessage']);

//HandleSubmit Controller
Route::post('form-submit', [HandleSubmitController::class, 'handleFormSubmission']);

//Courses Controller 
Route::get('courses_date', [CourseController::class, 'get_bydate']);
Route::get('courses', [CourseController::class, 'index']);

//Auth Controller
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/reset-password', [AuthController::class, 'resetPassword']);


//Harus memiliki authorization
Route::middleware('auth:sanctum')->group(function () {
    //CallApi Controller
    Route::get('/user-parent', [CallApiController::class, 'getUserParentData']);

    //Auth Controller
    Route::get('/user', [AuthController::class, 'getUserData']);

    //Parent Controller
    Route::get('parent-uid', [ParentsController::class, 'getParentsData']);
    Route::get('parents', [ParentsController::class, 'index']);
    Route::post('sub-parents', [ParentsController::class, 'double_sub']);
    Route::get('parents/{id}', [ParentsController::class, 'show']);
    Route::put('parents/{id}', [ParentsController::class, 'update']);
    Route::delete('parents/{id}', [ParentsController::class, 'destroy']);

    //Courses Controller
    Route::get('courses/{id}', [CourseController::class, 'show']);
    Route::post('courses', [CourseController::class, 'store']);
    Route::put('courses/{id}', [CourseController::class, 'update']);
    Route::delete('courses/{id}', [CourseController::class, 'destroy']);

    //HandleSubmit Controller
    Route::post('payment-sub', [HandleSubmitController::class, 'HandlePayment']);

    //Schedules Controller
    Route::get('schedules', [SchedulePrgController::class, 'index']);
    Route::post('schedules', [SchedulePrgController::class, 'store']);

    //Invitonal Controller
    Route::post('/voucher', [InviteController::class, 'create']);
    Route::get('/voucher', [InviteController::class, 'index']);
    Route::get('/voucher/{id}', [InviteController::class, 'show']);
    Route::put('/voucher/{id}', [InviteController::class, 'update']);
    Route::delete('/voucher/{id}', [InviteController::class, 'delete']);
    Route::post('discount-voc', [InviteController::class, 'validateVoucher']);

    //Payment_Sps Controller
    Route::get('payment-details/{paymentId}', [PaymentSpController::class, 'payment_details']);

    //Student Controller
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);

    //Kelas Controller
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::post('/kelas', [KelasController::class, 'store']);
    Route::put('/kelas/{id}', [KelasController::class, 'update']);
    Route::delete('/kelas/{id}', [KelasController::class, 'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});
