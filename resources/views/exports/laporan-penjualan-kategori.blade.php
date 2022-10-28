<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h4>Laporan Penjualan Kategori</h4>
    <br>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Jumlah Terjual (pcs)</th>
                <th>Pendapatan Kategori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $key => $value)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $value['qty'] }}</td>
                <td>{{ $value['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td>{{ $record->sum('qty') }}</td>
                <td>{{ $record->sum('total') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>