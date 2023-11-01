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
                <th>Tenant</th>
                <th>Waktu Transaksi</th>
                <th>ID Transaksi</th>
                <th>Total Product</th>
                <th>Fee</th>
                <th>Service Fee</th>
                <th>Sub Total</th>
                <th>Biaya Tambahan</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Jenis Transkasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{$value['tenant']}}</td>
                <td>{{$value['id_transaksi']}}</td>
                <td>{{$value['total_product']}} item</td>
                <td style="white-space: nowrap;">{{ $value['fee'] }}</td>
                <td style="white-space: nowrap;">{{ $value['service_fee'] }}</td>
                <td style="white-space: nowrap;">{{ $value['total_sub_total'] }}</td>
                <td style="white-space: nowrap;">{{ $value['total_addon'] }}</td>
                <td style="white-space: nowrap;">{{ $value['total'] }}</td>
                <td>{{$value['metode_pembayaran']}}</td>
                <td>{{$value['jenis_transaksi']}}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{$total_product}} item</td>
                <td>{{$fee}}</td>
                <td>{{$service_fee}}</td>
                <td>{{$total_sub_total}}</td>
                <td>{{$total_addon}}</td>
                <td>{{$total_total}}</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>