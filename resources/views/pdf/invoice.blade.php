<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { margin: 0; font-size: 26px; }
        .muted { color: #6b7280; }
        .header, .summary { width: 100%; margin-bottom: 24px; }
        .header td, .summary td { vertical-align: top; }
        .right { text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th, table.items td { border: 1px solid #d1d5db; padding: 8px; }
        table.items th { background: #f3f4f6; text-align: left; }
        .totals { width: 46%; margin-left: auto; margin-top: 18px; border-collapse: collapse; }
        .totals td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .grand { font-weight: bold; font-size: 14px; }
        .adjustment { color: #991b1b; }
        .footer { margin-top: 32px; border-top: 1px solid #d1d5db; padding-top: 12px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            <h1>INVOICE</h1>
            <div class="muted">{{ $invoice->invoice_no }}</div>
        </td>
        <td class="right">
            <strong>Status:</strong> {{ $invoice->status }}<br>
            <strong>Invoice date:</strong> {{ $invoice->invoice_date?->format('d M Y') }}<br>
            <strong>Due date:</strong> {{ $invoice->due_date?->format('d M Y') }}
        </td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td>
            <strong>Bill To</strong><br>
            {{ $invoice->customer?->display_name }}<br>
            {{ $invoice->customer?->contact_person }}<br>
            {{ $invoice->customer?->primary_email }}<br>
            {{ $invoice->customer?->primary_phone }}
        </td>
        <td class="right">
            <strong>Quotation:</strong> {{ $invoice->quotation?->quotation_code }}<br>
            <strong>Currency:</strong> {{ $invoice->currency }}
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th class="right">Qty</th>
            <th class="right">Unit price</th>
            <th class="right">Discount</th>
            <th class="right">Line total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoice->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->discount, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Invoice total copied from quotation {{ $invoice->quotation?->quotation_code }}.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="right">{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
    <tr><td>Discount</td><td class="right">{{ number_format((float) $invoice->discount_value, 2) }}</td></tr>
    <tr><td>Tax</td><td class="right">{{ number_format((float) $invoice->tax, 2) }}</td></tr>
    <tr class="grand"><td>Original Total</td><td class="right">{{ number_format((float) $invoice->grand_total, 2) }}</td></tr>
    @if ((float) $invoice->credited_total > 0)
        <tr class="adjustment"><td>Credit Notes</td><td class="right">-{{ number_format((float) $invoice->credited_total, 2) }}</td></tr>
    @endif
    <tr class="grand"><td>Net Invoice Total</td><td class="right">{{ number_format((float) $invoice->net_total, 2) }}</td></tr>
    @if ((float) $invoice->refunded_total > 0)
        <tr class="adjustment"><td>Refunded</td><td class="right">{{ number_format((float) $invoice->refunded_total, 2) }}</td></tr>
    @endif
    <tr><td>Net Paid</td><td class="right">{{ number_format((float) $invoice->total_paid, 2) }}</td></tr>
    <tr class="grand"><td>Balance Due</td><td class="right">{{ number_format((float) $invoice->balance_due, 2) }}</td></tr>
</table>

@if ($invoice->customer_notes)
    <div class="footer">
        <strong>Notes</strong><br>
        {!! nl2br(e($invoice->customer_notes)) !!}
    </div>
@endif
</body>
</html>
