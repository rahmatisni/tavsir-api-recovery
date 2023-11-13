<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents</title>
</head>

<body>
    <h4>Laporan Operasional Petugas</h4>
    <br>
    <h4>Tenant : {{ $nama_tenant }}</h4>
    <h4>Transaksi : Semua Transaksi</h4>
    <h4>Metode Bayar : Semua Metode Bayar</h4>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Periode</th>
                <th>Waktu Buka</th>
                <th>Waktu Tutup</th>
                <th>Waktu Rekap</th>
                <th>Kasir</th>
                <th>Nominal Uang Kembalian</th>
                <th>Total Pendapatan QR</th>
                <th>Total Pendapatan Pembayaran Digital</th>
                <th>Total Pendapatan Tunai</th>
                <th>Nominal Uang Tunai</th>
                <th>Total Addon</th>
                <th>Nominal Koreksi</th>
                <th>Selisih</th>
                <th>Keterangan Koreksi</th>
                <th>Total Nominal Rekap</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value->periode  }}</td>
                <td>{{ $value->waktu_buka }}</td>
                <td>{{ $value->waktu_tutup }}</td>
                <td>{{ $value->waktu_rekap }}</td>
                <td>{{ $value->kasir }}</td>
                <td>{{ $value->uang_kembalian }}</td>
                <td>{{ $value->qr }}</td>
                <td>{{ $value->digital }}</td>
                <td>{{ $value->tunai }}</td>
                <td>{{ $value->nominal_tunai}}</td>
                <td>{{ $value->total_addon }}</td>
                <td>{{ $value->koreksi }}</td>
                <td>{{ $value->selisih}}</td>
                <td>{{ $value->keterangan_koreksi }}</td>
                <td>{{ $value->total_rekap }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total</td>
                <td>{{ $total_qr }}</td>
                <td>{{ $total_digital }}</td>
                <td>{{ $total_tunai }}</td>
                <td>{{ $total_nominal_tunai }}</td>
                <td>{{ $total_addon }}</td>
                <td>{{ $total_koreksi }}</td>
                <td></td>
                <td></td>
                <td>{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>