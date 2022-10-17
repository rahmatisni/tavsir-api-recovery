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
    <h4>Transaksi : Semua Transaksi</h4>
    <br>
    <h4>Metode Bayar : Semua Metode Bayar</h4>
    <br>
    <h4>Tanggal Awal : </h4>
    <br>
    <h4>Tanggal Akhir : </h4>
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
                <td>{{ $value->start_date }}</td>
                <td>{{ $value->end_date }}</td>
                <td>{{ $value->end_date }}</td>
                <td>{{ $value->cashier->name ?? '' }}</td>
                <td>{{ $value->trans_cashbox->inital_cashbox ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->rp_qr ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->total_digital ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->rp_cash ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->cashbox ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->pengeluaran_cashbox ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->different_cashbox ?? 0 }}</td>
                <td>{{ $value->trans_cashbox->description ?? '' }}</td>
                <td>{{ $value->trans_cashbox->rp_total ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total</td>
                <td>{{ $record->sum('trans_cashbox.rp_qr')}}</td>
                <td>{{ $record->sum('trans_cashbox.total_digital')}}</td>
                <td>{{ $record->sum('trans_cashbox.rp_cash')}}</td>
                <td>{{ $record->sum('trans_cashbox.cashbox')}}</td>
                <td>{{ $record->sum('trans_cashbox.rp_qr')}}</td>
                <td></td>
                <td></td>
                <td>{{ $record->sum('trans_cashbox.rp_total')}}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>