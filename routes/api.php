<?php
// Fichier : routes/api.php — REMPLACE le contenu existant

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// ── Routes publiques (sans authentification) ───────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ── Routes protégées (Sanctum) ─────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);

    // Catégories
    Route::apiResource('categories', CategoryController::class);

    // Objectifs d'épargne
    Route::apiResource('goals', GoalController::class);
    Route::post('/goals/{goal}/deposit', [GoalController::class, 'deposit']); // Ajouter épargne
});