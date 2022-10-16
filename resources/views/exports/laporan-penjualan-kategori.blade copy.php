<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h5>Laporan Penjualan Kategori</h5>
    <br>
    <h4>Tanggal Awal : 2022-10-20</h4>
    <br>
    <h4>Tanggal Akhir : 2022-10-30</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Metode Pembayaran</th>
                <th>Jumlah Transaksi</th>
                <th>Total Transaksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $key => $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $key }}</td>
                <td>{{ $value['qty'] }}</td>
                <td>{{ $value['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td>{{$record->sum('qty')}}</td>
                <td>{{$record->sum('total')}}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>