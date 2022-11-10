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
                <th>Jenis Transaksi</th>
                <th>Jumlah</th>
                <th>Nominal Transaksi</th>
            </tr>
        </thead>
        @php
        $item_jumlah = 0;
        $item_total = 0;
        @endphp
        <tbody>
            @foreach($record as $key => $value)
            @php
            $item_jumlah += $value['jumlah'];
            $item_total += $value['total'];
            @endphp
            <tr>
                <td>{{$key}}</td>
                <td>{{$value['jumlah']}}</td>
                <td style="white-space: nowrap;">@rp($value['total'])</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td>{{$item_jumlah}} item</td>
                <td>@rp($item_total)</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>