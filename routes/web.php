<?php

use App\Http\Controllers\CreditNoteDocumentController;
use App\Http\Controllers\InvoiceDocumentController;
use App\Http\Controllers\PaymentDocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptVerificationController;
use App\Http\Controllers\RefundDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Receipt Verification
|--------------------------------------------------------------------------
*/

Route::get('/r/{hash}', [ReceiptVerificationController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('receipt.verify');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/finance/invoices/{invoice}/download',
        [InvoiceDocumentController::class, 'download']
    )->name('finance.invoices.download');

    Route::get(
        '/finance/credit-notes/{creditNote}/download',
        [CreditNoteDocumentController::class, 'download']
    )->name('finance.credit-notes.download');

    Route::get(
        '/finance/refunds/{refund}/download',
        [RefundDocumentController::class, 'download']
    )->name('finance.refunds.download');

    Route::get(
        '/finance/payments/{payment}/receipt',
        [PaymentDocumentController::class, 'receipt']
    )->name('finance.payments.receipt.download');

    Route::get(
        '/finance/payments/{payment}/statement',
        [PaymentDocumentController::class, 'statement']
    )->name('finance.payments.statement.download');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';
