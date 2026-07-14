<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $refund->refund_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { margin: 0; font-size: 26px; }
        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details td { border: 1px solid #d1d5db; padding: 9px; }
        .label { width: 36%; background: #f3f4f6; font-weight: bold; }
        .amount { font-size: 18px; font-weight: bold; }
        .footer { margin-top: 28px; border-top: 1px solid #d1d5db; padding-top: 12px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            <h1>REFUND CONFIRMATION</h1>
            <div class="muted">{{ $refund->refund_no }}</div>
        </td>
        <td class="right">
            <strong>Status:</strong> {{ $refund->status }}<br>
            <strong>Refund date:</strong> {{ $refund->refund_date?->format('d M Y') }}
        </td>
    </tr>
</table>

<table class="details">
    <tr><td class="label">Customer</td><td>{{ $refund->customer?->display_name }}</td></tr>
    <tr><td class="label">Invoice</td><td>{{ $refund->invoice?->invoice_no }}</td></tr>
    <tr><td class="label">Payment</td><td>{{ $refund->payment?->payment_no }}</td></tr>
    @if ($refund->creditNote)
        <tr><td class="label">Credit Note</td><td>{{ $refund->creditNote->credit_note_no }}</td></tr>
    @endif
    <tr><td class="label">Refund Method</td><td>{{ $refund->refund_method }}</td></tr>
    <tr><td class="label">Transaction Reference</td><td>{{ $refund->transaction_reference ?: '-' }}</td></tr>
    <tr><td class="label">Refund Amount</td><td class="amount">{{ $refund->invoice?->currency }} {{ number_format((float) $refund->amount, 2) }}</td></tr>
</table>

<div class="footer">
    <strong>Reason</strong><br>
    {!! nl2br(e($refund->reason)) !!}
</div>

@if ($refund->status === 'Cancelled' && $refund->cancel_reason)
    <div class="footer">
        <strong>Cancellation Reason</strong><br>
        {!! nl2br(e($refund->cancel_reason)) !!}
    </div>
@endif
</body>
</html>
