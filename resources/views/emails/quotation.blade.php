@extends('emails.layout')

@section('title')
Quotation
@endsection

@section('content')

<h2 style="margin-top:0;">
    Quotation
</h2>

<p>

Hello {{ $quotation->customer->display_name }},

</p>

<p>

Thank you for your interest in our services.

Please find your quotation attached to this email.

</p>

<table>

<tr>

<td><strong>Quotation</strong></td>

<td>{{ $quotation->quotation_code }}</td>

</tr>

<tr>

<td><strong>Date</strong></td>

<td>{{ optional($quotation->quotation_date)->format('d M Y') }}</td>

</tr>

<tr>

<td><strong>Valid Until</strong></td>

<td>{{ optional($quotation->valid_until)->format('d M Y') }}</td>

</tr>

<tr>

<td><strong>Grand Total</strong></td>

<td>

₹ {{ number_format($quotation->grand_total,2) }}

</td>

</tr>

</table>

<p style="margin-top:25px;">

If you have any questions regarding this quotation, simply reply to this email.

</p>

@endsection