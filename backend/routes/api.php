<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\DashboardPreferenceController;
use App\Http\Controllers\SavedFilterController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard (accessible by all authenticated users)
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/line-chart', [DashboardController::class, 'lineChart']);
        Route::get('/bar-chart', [DashboardController::class, 'barChart']);
        Route::get('/pie-chart', [DashboardController::class, 'pieChart']);
        Route::get('/preferences', [DashboardPreferenceController::class, 'show']);
        Route::put('/preferences', [DashboardPreferenceController::class, 'update']);
        Route::delete('/preferences', [DashboardPreferenceController::class, 'destroy']);
    });

    // Data Management (read access for all authenticated users)
    Route::get('/data', [DataController::class, 'index']);
    Route::get('/data/export', [DataController::class, 'export']);
    Route::get('/data/{id}', [DataController::class, 'show']);

    // Data Management (write operations - accessible by all authenticated users)
    Route::post('/data', [DataController::class, 'store']);
    Route::put('/data/{id}', [DataController::class, 'update']);
    Route::delete('/data/{id}', [DataController::class, 'destroy']);
    Route::post('/data/batch-delete', [DataController::class, 'batchDestroy']);
    Route::post('/data/batch-update', [DataController::class, 'batchUpdate']);

    // Data Import (accessible by all authenticated users)
    Route::post('/import', [ImportController::class, 'import']);
    Route::get('/import/history', [ImportController::class, 'history']);

    // Saved Filters (accessible by all authenticated users)
    Route::get('/filters', [SavedFilterController::class, 'index']);
    Route::post('/filters', [SavedFilterController::class, 'store']);
    Route::get('/filters/{id}', [SavedFilterController::class, 'show']);
    Route::put('/filters/{id}', [SavedFilterController::class, 'update']);
    Route::delete('/filters/{id}', [SavedFilterController::class, 'destroy']);

    // Admin-only routes (require admin role)
    Route::middleware('role:admin')->group(function () {
        // User Management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // Audit Trail
        Route::get('/audit-trail', [AuditTrailController::class, 'index']);
        Route::get('/audit-trail/{id}', [AuditTrailController::class, 'show']);

        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show']);
    });
});
