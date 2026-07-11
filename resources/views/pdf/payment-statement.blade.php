@extends('pdf.layouts.document')

@section('title')
Payment Statement {{ $payment->receipt_number }}
@endsection

@section('document-title')
Payment Statement
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

<div class="section">

    @php

    $quotation = $payment->quotation->fresh('payments');

    $previousPayments = $quotation->payments
        ->where('id', '!=', $payment->id)
        ->sum('amount');

@endphp


    <table class="table">

        <tr>
            <td class="label">Quotation Total</td>
            <td>
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->grand_total, 2) }}
            </td>
        </tr>

        <tr>
            <td class="label">Previous Payments</td>
            <td>
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($previousPayments, 2) }}
            </td>
        </tr>

        <tr>
            <td class="label">Current Payment</td>
            <td>
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($payment->amount, 2) }}
            </td>
        </tr>

        <tr>
            <td class="label"><strong>Total Paid</strong></td>
            <td>
                <strong>
                    {{ $company->currency_symbol ?: '₹' }}
                    {{ number_format($quotation->total_paid, 2) }}
                </strong>
            </td>
        </tr>

        <tr>
            <td class="label">Outstanding Balance</td>
            <td>
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->balance_due, 2) }}
            </td>
        </tr>

        <tr>
            <td class="label">Payment Status</td>
            <td>
                <strong>{{ $quotation->payment_status }}</strong>
            </td>
        </tr>

    </table>

</div>

<div class="section">

    <h3 style="margin-bottom:10px;">
        Payment History
    </h3>

    <table class="table">

        <thead>

            <tr>

                <th>Date</th>

                <th>Receipt No.</th>

                <th>Method</th>

                <th style="text-align:right;">
                    Amount
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach($quotation->payments()->orderBy('payment_date')->get() as $history)

                <tr>

                    <td>
                        {{ optional($history->payment_date)->format('d M Y') }}
                    </td>

                    <td>
                        {{ $history->receipt_number }}
                    </td>

                    <td>
                        {{ $history->payment_method }}
                    </td>

                    <td style="text-align:right;">

                        {{ $company->currency_symbol ?: '₹' }}

                        {{ number_format($history->amount, 2) }}

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

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