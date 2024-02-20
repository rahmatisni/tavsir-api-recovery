<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    body{
        font-family: Arial, Helvetica, sans-serif;
        font-size: small;
    }
    .page_break { page-break-before: always; }
    .table table, .table td, .table th {
        border: 1px solid;
        padding: 0.3em;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .nowrap{
        white-space: nowrap;
    }

    .text-center{
        text-align: center;
    }
    .text-right{
        text-align: right;
    }

    .blue-sky{
        background-color: #87CEEB;
    }
</style>

<body>
<h2 class="text-center">Laporan Rekap Pendapatan</h2>
    <table class="table">
        <tbody>
            <tr>
                <td>Kasir</td>
                <td>: {{$nama_kasir}}</td>
                <td>Periode</td>
                <td>: {{$periode}}</td>
            </tr>
            <tr>
                <td>Waktu Buka</td>
                <td>: {{$waktu_buka}}</td>
                <td>Tenant</td>
                <td>: {{$nama_tenant}}</td>
            </tr>
            <tr>
                <td>Waktu Rekap</td>
                <td>: {{$waktu_tutup}}</td>
                <td>Rest Area</td>
                <td>: {{$rest_area_name}}</td>
            </tr>
        </tbody>
    </table>
    <br>
    <table class="table  text-center">
        <thead class="blue-sky">
            <tr>
                <th>Total Pendapatan</th>
                <th>Total Penjualan</th>
                <th>Total Biaya Tambahan</th>
                <th>Total Refund</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $total_pendapatan }}</td>
                <td>{{ $total_penjualan }}</td>
                <td>{{ $total_biaya_tambahan }}</td>
                <td>{{ $total_refund }}</td>
            </tr>
        </tbody>
    </table>
    <br>
    <table class="table  text-center">
        <thead class="blue-sky">
            <tr>
                <th>Bagi Hasil</th>
                <th>Nominal Bagi Hasil</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sharing as $k => $v)
            <tr>
                <td>{{$v['label']}}</td>
                <td>Rp. @rp($v['value'])</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <table class="table">
        <thead class="blue-sky">
            <tr>
                <th>Metode Pembayaran</th>
                <th colspan="2">Detail</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="6">Pembayaran Tunai</td>
                <td>Total Pendapatan</td>
                <td>Rp. @rp($pembayaran_tunai)</td>
            </tr>
            <tr>
                <td>Modal Uang Kembalian</td>
                <td>Rp. @rp($uang_kembalian)</td>
            </tr>
            <tr>
                <td>Nominal Uang Tunai</td>
                <td>Rp. @rp($pembayaran_tunai)</td>
            </tr>
            <tr>
                <td>Selisih Tunai</td>
                <td>Rp. @rp($selisih_tunai)</td>
            </tr>
            <tr>
                <td>Nominal Koreksi</td>
                <td>Rp. @rp($nominal_koreski)</td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>{{$keterangan}}</td>
            </tr>
            <tr>
                <td>Pembayaran GetpayQR</td>
                <td>Total Pendapatan</td>
                <td>Rp. @rp($pembayaran_qr)</td>
            </tr>
            <tr>
                <td rowspan="5">Pembayaran Digital</td>
                <td>Total Pendapatan</td>
                <td>Rp. @rp($pembayaran_digital)</td>
            </tr>
            <tr>
                <td>BRI Virtual Account</td>
                <td>Rp. @rp($bri_va)</td>
            </tr>
            <tr>
                <td>BNI Virtual Account</td>
                <td>Rp. @rp($bni_va)</td>
            </tr>
            <tr>
                <td>Mandiri Virtual Account</td>
                <td>Rp. @rp($mandiri_va)</td>
            </tr>
            <tr>
                <td>LinkAja</td>
                <td>Rp. @rp($link_aja)</td>
            </tr>
        </tbody>
    </table>
    <br>
    <div class="page_break"></div>

    <h2 class="text-center">Detail Transaksi</h2>
    <table class="table">
        <thead class="blue-sky">
            <tr>
                <th>No.</th>
                <th>Waktu Transaksi</th>
                <th>ID Transaksi</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Jenis Transkasi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @if(count($order) == 0)
            <tr class="nowrap">
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endif
            @foreach($order as $value)
            <tr class="nowrap">
                <td>{{$loop->iteration}}</td>
                <td>{{$value['waktu_transaksi']}}</td>
                <td>{{$value['id_transaksi']}}</td>
                <td class="text-center">{{$value['total_product']}} item</td>
                <td class="text-right">@rp($value['total'])</td>
                <td>{{$value['metode_pembayaran']}}</td>
                <td>{{$value['jenis_transaksi']}}</td>
                <td>{{$value['status']}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>