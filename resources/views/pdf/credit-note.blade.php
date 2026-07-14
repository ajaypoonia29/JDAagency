<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $creditNote->credit_note_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { margin: 0; font-size: 26px; }
        .header, .summary { width: 100%; margin-bottom: 24px; }
        .header td, .summary td { vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th, table.items td { border: 1px solid #d1d5db; padding: 8px; }
        table.items th { background: #f3f4f6; text-align: left; }
        .total { width: 42%; margin-left: auto; margin-top: 18px; border-collapse: collapse; }
        .total td { padding: 7px; border-bottom: 1px solid #e5e7eb; }
        .grand { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 28px; border-top: 1px solid #d1d5db; padding-top: 12px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            <h1>CREDIT NOTE</h1>
            <div class="muted">{{ $creditNote->credit_note_no }}</div>
        </td>
        <td class="right">
            <strong>Status:</strong> {{ $creditNote->status }}<br>
            <strong>Issue date:</strong> {{ $creditNote->issue_date?->format('d M Y') }}<br>
            <strong>Invoice:</strong> {{ $creditNote->invoice?->invoice_no }}
        </td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td>
            <strong>Customer</strong><br>
            {{ $creditNote->customer?->display_name }}<br>
            {{ $creditNote->customer?->primary_email }}
        </td>
        <td class="right"><strong>Currency:</strong> {{ $creditNote->currency }}</td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th class="right">Subtotal</th>
            <th class="right">Tax</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($creditNote->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->tax, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="total">
    <tr><td>Subtotal</td><td class="right">{{ number_format((float) $creditNote->subtotal, 2) }}</td></tr>
    <tr><td>Tax</td><td class="right">{{ number_format((float) $creditNote->tax, 2) }}</td></tr>
    <tr class="grand"><td>Credit Total</td><td class="right">{{ number_format((float) $creditNote->grand_total, 2) }}</td></tr>
</table>

<div class="footer">
    <strong>Reason</strong><br>
    {!! nl2br(e($creditNote->reason)) !!}
</div>

@if ($creditNote->status === 'Void' && $creditNote->void_reason)
    <div class="footer">
        <strong>Void Reason</strong><br>
        {!! nl2br(e($creditNote->void_reason)) !!}
    </div>
@endif
</body>
</html>
