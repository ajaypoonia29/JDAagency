@extends('pdf.layouts.document')

@section('title')
Receipt {{ $payment->receipt_number }}
@endsection

@section('document-title')
Payment Receipt
@endsection

@section('content')

<div class="section">

    <table class="table">

        <tr>
            <td class="label">Receipt Number</td>
            <td>{{ $payment->receipt_number }}</td>

            <td class="label">Payment Date</td>
            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
        </tr>

        <tr>
            <td class="label">Payment Number</td>
            <td>{{ $payment->payment_no }}</td>

            <td class="label">Payment Method</td>
            <td>{{ $payment->payment_method }}</td>
        </tr>

        <tr>
            <td class="label">Quotation</td>
            <td>{{ $payment->quotation->quotation_code }}</td>

            <td class="label">Transaction Ref.</td>
            <td>{{ $payment->transaction_reference ?: '-' }}</td>
        </tr>

        <tr>

    <td class="label">

        Customer Details

    </td>

    <td colspan="3">

        <strong>

            {{ $payment->customer->display_name }}

        </strong>

        @php

            $businessName = $payment->customer->legal_name
                ?: $payment->customer->company_name;

        @endphp

        @if(
            $businessName &&
            $businessName !== $payment->customer->display_name
        )

            <br>

            <strong>Business / Legal Name:</strong>

            {{ $businessName }}

        @endif

        @if($payment->customer->primary_phone)

            <br>

            <strong>Phone:</strong>

            {{ $payment->customer->primary_phone }}

        @endif

        @if($payment->customer->primary_email)

            <br>

            <strong>Email:</strong>

            {{ $payment->customer->primary_email }}

        @endif

    </td>

</tr>
    </table>

</div>

<div class="amount-box">

    <div class="amount-title">

        Amount Received

    </div>

    <div class="amount">

        {{ $company->currency_symbol ?: '₹' }}
        {{ number_format($payment->amount, 2) }}

    </div>

</div>

@if($payment->notes)

<div class="section">

    <table class="table">

        <tr>

            <td class="label">

                Notes

            </td>

            <td>

                {{ $payment->notes }}

            </td>

        </tr>

    </table>

</div>

@endif

<div
    class="section"
    style="
        margin-top:10px;
        text-align:center;
        font-size:11px;
        color:#555;
    "
>

    Thank you for your payment.

    We appreciate your business and look forward to serving you again.

</div>
@endsection