@extends('emails.layout')

@section('title')
Payment Receipt
@endsection

@section('content')

<h2 style="margin-top:0;">
    Payment Receipt
</h2>

<p>

Hello <strong>{{ $payment->customer->display_name }}</strong>,

</p>

<p>

Thank you for your payment.

Your payment has been successfully received and recorded.

</p>

<table>

<tr>

    <td class="label">
        Receipt Number
    </td>

    <td>
        {{ $payment->receipt_number }}
    </td>

</tr>

<tr>

    <td class="label">
        Quotation
    </td>

    <td>
        {{ $payment->quotation->quotation_code }}
    </td>

</tr>

<tr>

    <td class="label">
        Payment Date
    </td>

    <td>
        {{ optional($payment->payment_date)->format('d M Y') }}
    </td>

</tr>

<tr>

    <td class="label">
        Payment Method
    </td>

    <td>
        {{ $payment->payment_method }}
    </td>

</tr>

<tr>

    <td class="label">
        Amount Received
    </td>

    <td>

        {{ \App\Services\CompanyService::company()->currency_symbol ?: '₹' }}

        {{ number_format($payment->amount, 2) }}

    </td>

</tr>

@if($payment->transaction_reference)

<tr>

    <td class="label">
        Transaction Reference
    </td>

    <td>
        {{ $payment->transaction_reference }}
    </td>

</tr>

@endif

</table>

@if($payment->notes)

<p style="margin-top:20px;">

<strong>Notes</strong>

<br>

{{ $payment->notes }}

</p>

@endif

<p style="margin-top:30px;">

Your official payment receipt is attached to this email for your records.

</p>

<p>

Thank you for choosing

<strong>

{{ \App\Services\CompanyService::company()->company_name }}

</strong>.

</p>

@endsection