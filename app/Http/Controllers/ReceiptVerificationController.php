<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class ReceiptVerificationController extends Controller
{
    public function show(string $hash)
    {
        $payment = Payment::with([
            'customer',
            'quotation',
        ])
            ->where('verification_hash', $hash)
            ->where('receipt_generated', true)
            ->first();

        if (! $payment) {
            abort(404);
        }

        return view('receipt.verify', [
            'payment' => $payment,
        ]);
    }
}