<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>
@yield('title')
</title>

<style>

body{

    margin:0;
    padding:30px;

    font-family:Arial,Helvetica,sans-serif;

    background:#f3f4f6;

    color:#374151;

}

.wrapper{

    max-width:700px;

    margin:auto;

    background:#ffffff;

    border-radius:10px;

    overflow:hidden;

    border:1px solid #e5e7eb;

}

.header{

    background:#1d4ed8;

    color:#ffffff;

    padding:25px;

}

.header h1{

    margin:0;

    font-size:24px;

}

.content{

    padding:30px;

}

.footer{

    padding:20px;

    background:#f9fafb;

    text-align:center;

    font-size:12px;

    color:#6b7280;

}

table{

    width:100%;

    border-collapse:collapse;

    margin-top:20px;

}

td{

    border:1px solid #e5e7eb;

    padding:10px;

}

.label{

    width:220px;

    font-weight:bold;

    background:#f8fafc;

}

.button{

    display:inline-block;

    padding:12px 20px;

    background:#2563eb;

    color:#ffffff;

    text-decoration:none;

    border-radius:6px;

}

</style>

</head>

<body>

<div class="wrapper">

<div class="header">

<h1>

{{ \App\Services\CompanyService::company()->company_name }}

</h1>

</div>

<div class="content">

@yield('content')

</div>

<div class="footer">

{{ \App\Services\CompanyService::company()->company_name }}

<br>

{{ \App\Services\CompanyService::company()->email }}

@if(\App\Services\CompanyService::company()->phone)

<br>

{{ \App\Services\CompanyService::company()->phone }}

@endif

</div>

</div>

</body>

</html>