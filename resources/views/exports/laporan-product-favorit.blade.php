<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h4>Laporan Product Favorit</h4>
    <br>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>SKU</th>
                <th>Nama Product</th>
                <th>Kategori</th>
                <th>Jumlah Terjual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $key => $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value['sku'] }}</td>
                <td>{{ $key }}</td>
                <td>{{ $value['category'] }}</td>
                <td>{{ $value['qty'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>