@extends('emails.layout')

@section('title')
Invoice {{ $invoice->invoice_no }}
@endsection

@section('content')
<h2 style="margin-top:0;">Invoice</h2>

<p>Hello <strong>{{ $invoice->customer?->display_name }}</strong>,</p>

<p>
    Please find invoice <strong>{{ $invoice->invoice_no }}</strong>
    attached to this email.
</p>

<table>
    <tr>
        <td class="label">Invoice Number</td>
        <td>{{ $invoice->invoice_no }}</td>
    </tr>
    <tr>
        <td class="label">Invoice Date</td>
        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Due Date</td>
        <td>{{ $invoice->due_date?->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Net Invoice Total</td>
        <td>{{ $invoice->currency }} {{ number_format((float) $invoice->net_total, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Balance Due</td>
        <td>{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td>{{ $invoice->status }}</td>
    </tr>
</table>

<p style="margin-top:25px;">
    Please reply to this email if you have any questions about the invoice.
</p>
@endsection
