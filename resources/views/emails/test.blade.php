@extends('emails.layout')

@section('title')
SMTP Test
@endsection

@section('content')

<h2 style="margin-top:0;">
    SMTP Configuration Test
</h2>

<p>

Congratulations!

</p>

<p>

Your SMTP configuration has been verified successfully.

AgencyOS is able to send emails using your configured mail server.

</p>

<table>

<tr>

    <td class="label">
        Company
    </td>

    <td>
        {{ \App\Services\CompanyService::company()->company_name }}
    </td>

</tr>

<tr>

    <td class="label">
        Status
    </td>

    <td>
        ✅ SMTP Working
    </td>

</tr>

<tr>

    <td class="label">
        Generated
    </td>

    <td>
        {{ now()->format('d M Y H:i') }}
    </td>

</tr>

</table>

<p style="margin-top:30px;">

This is a system-generated email from AgencyOS.

</p>

@endsection