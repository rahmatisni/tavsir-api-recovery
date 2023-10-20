<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h4>Laporan Penjualan Product</h4>
    <br>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Product</th>
                <th>Kategori</th>
                <th>Jumlah Terjual</th>
                <th>Harga Product</th>
                <th>Pendapatan Product</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{ $value->sku }}</td>
                <td>{{ $value->nama_product }}</td>
                <td>{{ $value->kategori}}</td>
                <td>{{ $value->jumlah }}</td>
                <td>{{ $value->harga }}</td>
                <td>{{ $value->pendapatan }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ $total_jumlah }}</td>
                <td></td>
                <td>{{ $total_pendapatan }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>