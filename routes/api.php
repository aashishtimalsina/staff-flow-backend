<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\BookingRequestController;
use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\TimesheetController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JobRoleController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\CompanyProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/password/reset-request', [AuthController::class, 'resetRequest']);
        Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    });
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Users (Admin only)
    Route::apiResource('users', UserController::class);

    // Job Roles
    Route::apiResource('job-roles', JobRoleController::class);

    // Candidates
    Route::prefix('candidates')->group(function () {
        Route::get('/', [CandidateController::class, 'index']);
        Route::post('/', [CandidateController::class, 'store']);
        Route::get('/{candidate}', [CandidateController::class, 'show']);
        Route::put('/{candidate}', [CandidateController::class, 'update']);
        Route::delete('/{candidate}', [CandidateController::class, 'destroy']);

        // Compliance
        Route::get('/{candidate}/compliance', [CandidateController::class, 'getCompliance']);
        Route::post('/{candidate}/compliance/{compliance}/upload', [CandidateController::class, 'uploadCompliance']);
        Route::put('/{candidate}/compliance/{compliance}', [CandidateController::class, 'updateCompliance']);
    });

    // Clients
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{client}', [ClientController::class, 'show']);
        Route::put('/{client}', [ClientController::class, 'update']);
        Route::delete('/{client}', [ClientController::class, 'destroy']);

        // Rate Cards
        Route::get('/{client}/rate-cards', [ClientController::class, 'getRateCards']);
        Route::post('/{client}/rate-cards', [ClientController::class, 'createRateCard']);
        Route::get('/{client}/rate-cards/applicable', [ClientController::class, 'getApplicableRate']);
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingRequestController::class, 'index']);
        Route::post('/', [BookingRequestController::class, 'store']);
        Route::get('/{booking}', [BookingRequestController::class, 'show']);
        Route::put('/{booking}', [BookingRequestController::class, 'update']);
        Route::post('/{booking}/cancel', [BookingRequestController::class, 'cancel']);
    });

    // Assignments
    Route::apiResource('assignments', AssignmentController::class);

    // Timesheets
    Route::prefix('timesheets')->group(function () {
        Route::get('/', [TimesheetController::class, 'index']);
        Route::get('/{timesheet}', [TimesheetController::class, 'show']);
        Route::put('/{timesheet}', [TimesheetController::class, 'update']);
        Route::post('/{timesheet}/submit', [TimesheetController::class, 'submit']);
        Route::post('/{timesheet}/approve', [TimesheetController::class, 'approve']);
        Route::post('/{timesheet}/reject', [TimesheetController::class, 'reject']);
    });

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{invoice}', [InvoiceController::class, 'show']);
        Route::put('/{invoice}', [InvoiceController::class, 'update']);
        Route::post('/{invoice}/status', [InvoiceController::class, 'updateStatus']);
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // Audit Logs (Admin only)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Company Profile
    Route::get('/company-profile', [CompanyProfileController::class, 'show']);
    Route::put('/company-profile', [CompanyProfileController::class, 'update']);
});
