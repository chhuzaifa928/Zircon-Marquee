<?php

use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\ReportController;
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

    // Report PDFs (SRS §15) — date range via ?from=&to=.
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/cash-closing', [ReportController::class, 'cashClosing'])->name('cash-closing');
        Route::get('/supplier-summary', [ReportController::class, 'supplierSummary'])->name('supplier-summary');
        Route::get('/bookings-master', [ReportController::class, 'bookingsMaster'])->name('bookings-master');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
    });
});
