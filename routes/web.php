<?php

use App\Http\Controllers\BookingDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Printable booking documents (SRS §14). Guarded by auth so only panel users
// can open a host's documents.
Route::middleware('auth')->group(function () {
    Route::get('/bookings/{booking}/sheet', [BookingDocumentController::class, 'bookingSheet'])
        ->name('bookings.sheet');
    Route::get('/bookings/{booking}/advance-receipt', [BookingDocumentController::class, 'advanceReceipt'])
        ->name('bookings.advance-receipt');
});
