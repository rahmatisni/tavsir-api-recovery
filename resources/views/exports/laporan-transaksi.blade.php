<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h4>Laporan Transaksi</h4>
    <br>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Tanggal Awal: {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir: {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>Waktu Transaksi</th>
                <th>ID Transaksi</th>
                <th>Total Product</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Jenis Transkasi</th>
            </tr>
        </thead>
            @php
                $item_count = 0;
            @endphp
        <tbody>
            @foreach($record as $value)
            @php
                $count = $value->detil->count();
                $item_count += $count;
            @endphp
            <tr>
                <td>{{$value->created_at}}</td>
                <td>{{$value->order_id}}</td>
                <td>{{$count}} item</td>
                <td style="white-space: nowrap;">@rp($value->total)</td>
                <td>{{$value->payment_method->name ?? ''}}</td>
                <td>{{$value->labelOrderType()}}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td>{{$item_count}} item</td>
                <td>@rp($record->sum('total'))</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>