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
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{$value->created_at}}</td>
                <td>{{$value->order_id}}</td>
                <td>{{$value->detil->count()}} item</td>
                <td style="white-space: nowrap;">@rp($value->total)</td>
                <td>{{$value->payment_method->name ?? ''}}</td>
                <td>{{$value->labelOrderType()}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>