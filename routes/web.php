<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => 'BudgetTrack API', 'status' => 'running']);
});

// Route nécessaire pour que Password::sendResetLink() génère le bon lien
Route::get('/reset-password/{token}', function (string $token) {
    // Redirige vers le frontend React
    return redirect('http://localhost:5173/reset-password?token=' . $token . '&email=' . request('email'));
})->name('password.reset');