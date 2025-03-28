<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h4>Laporan Invoice</h4>
    <br>
    <h4>Status : {{ $status }}</h4>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Tanggal Awal: {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir: {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu terbit invoice</th>
                <th>No. nvoice</th>
                <th>Waktu terbit kwitansi</th>
                <th>No Kwitansi</th>
                <th>Kasir</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value->claim_date }}</td>
                <td>{{ $value->invoice_id }}</td>
                <td>{{ $value->paid_date }}</td>
                <td>{{ $value->kwitansi_id }}</td>
                <td>{{ $value->cashier->name ?? '' }}</td>
                <td>{{ $value->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>