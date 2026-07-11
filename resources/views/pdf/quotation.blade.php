@extends('pdf.layouts.document')

@section('title')
Quotation {{ $quotation->quotation_code }}
@endsection

@section('document-title')
Quotation
@endsection

@section('content')

<div class="section">

    <table class="table">

        <tr>

            <td class="label">Quotation No.</td>
            <td>{{ $quotation->quotation_code }}</td>

            <td class="label">Quotation Date</td>
            <td>{{ optional($quotation->quotation_date)->format('d M Y') }}</td>

        </tr>

        <tr>

            <td class="label">Valid Until</td>
            <td>{{ optional($quotation->valid_until)->format('d M Y') }}</td>

            <td class="label">Status</td>
            <td>{{ $quotation->status }}</td>

        </tr>

    </table>

</div>

<div class="section">

    <table class="table">

        <tr>

            <td class="label">

                Customer Details

            </td>

            <td colspan="3">

                <strong>

                    {{ $quotation->customer->display_name }}

                </strong>

                @php

                    $businessName =
                        $quotation->customer->legal_name
                        ?: $quotation->customer->company_name;

                @endphp

                @if(
                    $businessName &&
                    $businessName !== $quotation->customer->display_name
                )

                    <br>

                    <strong>Business / Legal Name:</strong>

                    {{ $businessName }}

                @endif

                @if($quotation->customer->primary_phone)

                    <br>

                    <strong>Phone:</strong>

                    {{ $quotation->customer->primary_phone }}

                @endif

                @if($quotation->customer->primary_email)

                    <br>

                    <strong>Email:</strong>

                    {{ $quotation->customer->primary_email }}

                @endif

            </td>

        </tr>

    </table>

</div>

<div class="section">

    <table class="table">

        <thead
    style="
        background:#f3f4f6;
        font-weight:bold;
    "
>

        <tr>

            <th style="width:28%;">Service</th>

            <th>Description</th>

            <th style="width:8%; text-align:center;">Qty</th>

            <th style="width:15%; text-align:right;">Unit Price</th>

            <th style="width:15%; text-align:right;">Discount</th>

            <th style="width:18%; text-align:right;">Line Total</th>

        </tr>

        </thead>

        <tbody>

        @foreach($quotation->items as $item)

        <tr>

            <td>{{ optional($item->service)->service_name }}</td>

            <td>{{ $item->description ?: '-' }}</td>

            <td style="text-align:center;">
                {{ $item->quantity }}
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($item->unit_price,2) }}
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($item->discount,2) }}
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($item->line_total,2) }}
            </td>

        </tr>

        @endforeach

        </tbody>

    </table>

</div>

<div
    class="section"
    style="margin-top:20px;"
>

    <table
        class="table"
        style="width:55%; margin-left:auto;"
    >

        <tr>

            <td class="label">
                Subtotal
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->subtotal,2) }}
            </td>

        </tr>

        <tr>

            <td class="label">
                Discount
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->discount_value,2) }}
            </td>

        </tr>

        <tr>

            <td class="label">
                GST
            </td>

            <td style="text-align:right;">
                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->tax,2) }}
            </td>

        </tr>

        <tr>

            <td
    class="label"
    style="
        font-size:16px;
        font-weight:bold;
        background:#f3f4f6;
    "
>
                Grand Total
            </td>

            <td
    style="
        text-align:right;
        font-size:18px;
        font-weight:bold;
        color:#059669;
        background:#f3f4f6;
    "
>

                {{ $company->currency_symbol ?: '₹' }}
                {{ number_format($quotation->grand_total,2) }}

            </td>

        </tr>

    </table>

</div>

@if($quotation->customer_notes)

<div class="section">

    <table class="table">

        <tr>

            <td class="label">
                Customer Notes
            </td>

            <td>
                {{ $quotation->customer_notes }}
            </td>

        </tr>

    </table>

</div>

@endif
<div class="section">

    <table class="table">

        <tr>

            <td class="label">

                Terms & Conditions

            </td>

            <td>

                <ol style="margin:0;padding-left:18px;">

                    <li>
                        This quotation is valid until
                        <strong>
                            {{ optional($quotation->valid_until)->format('d M Y') }}
                        </strong>.
                    </li>

                    <li>
                        Prices are subject to applicable taxes unless otherwise stated.
                    </li>

                    <li>
                        Any additional work outside this quotation will be charged separately.
                    </li>

                    <li>
                        Work will commence after confirmation and any applicable advance payment.
                    </li>

                    <li>
                        This quotation is confidential and intended only for the named customer.
                    </li>

                </ol>

            </td>

        </tr>

    </table>

</div>

<div class="section" style="margin-top:35px;">

    <table style="width:100%; border:none;">

        <tr>

            <td style="width:45%; border:none; text-align:center;">

                <div
    style="
        margin-top:65px;
        border-top:2px solid #333;
    "
></div>

                <strong>Customer Acceptance</strong>

                <br>

                Name & Signature

            </td>

            <td style="width:10%; border:none;"></td>

            <td style="width:45%; border:none; text-align:center;">

                <div style="margin-top:55px;border-top:1px solid #333;"></div>

                <strong>Authorized Signatory</strong>

                <br>

                {{ $company->company_name }}

            </td>

        </tr>

    </table>

</div>

<div
    class="section"
    style="
        margin-top:20px;
        text-align:center;
        font-size:11px;
        color:#555;
    "
>

    Thank you for considering our services.

    We look forward to working with you.

</div>
@endsection