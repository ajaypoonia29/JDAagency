<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation</title>

    <style>

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
            color:#222;
        }

        h1,h2,h3{
            margin:0;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            background:#f3f3f3;
            border:1px solid #ccc;
            padding:8px;
            text-align:left;
        }

        td{
            border:1px solid #ccc;
            padding:8px;
        }

        .right{
            text-align:right;
        }

        .center{
            text-align:center;
        }

        .mt-20{
            margin-top:20px;
        }

        .totals{
            width:320px;
            float:right;
        }

    </style>

</head>

<body>

<h1>Quotation</h1>

<hr>

<p>
<strong>Quotation #</strong>
{{ $quotation->quotation_code }}
</p>

<p>
<strong>Date:</strong>
{{ optional($quotation->quotation_date)->format('d M Y') }}
</p>

<p>
<strong>Valid Until:</strong>
{{ optional($quotation->valid_until)->format('d M Y') }}
</p>

<br>

<table>

<thead>

<tr>

<th>Service</th>
<th>Description</th>
<th>Qty</th>
<th>Price</th>
<th>Discount</th>
<th>Total</th>

</tr>

</thead>

<tbody>

@foreach($quotation->items as $item)

<tr>

<td>{{ optional($item->service)->service_name }}</td>

<td>{{ $item->description }}</td>

<td class="center">{{ $item->quantity }}</td>

<td class="right">
₹ {{ number_format($item->unit_price,2) }}
</td>

<td class="right">
₹ {{ number_format($item->discount,2) }}
</td>

<td class="right">
₹ {{ number_format($item->line_total,2) }}
</td>

</tr>

@endforeach

</tbody>

</table>

<br>

<table class="totals">

<tr>
<td>Subtotal</td>
<td class="right">
₹ {{ number_format($quotation->subtotal,2) }}
</td>
</tr>

<tr>
<td>GST</td>
<td class="right">
₹ {{ number_format($quotation->tax,2) }}
</td>
</tr>

<tr>

<th>Grand Total</th>

<th class="right">
₹ {{ number_format($quotation->grand_total,2) }}
</th>

</tr>

</table>

</body>

</html>