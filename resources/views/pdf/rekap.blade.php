<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
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
</style>
<body>
    <table>
        <tbody style="font-weight: bold;">
            <tr>
                <td>
                    <img style="width: 200px;" src="{{ url('/img/getpay-logo.png') }}" alt="logo">
                </td>
            </tr>
            <br>
            <tr>
                <td>Rekap Pendapatan</td>
                <td>Periode : {{$periode}}</td>
            </tr>
            <tr>
                <td>
                    Kasir: {{$nama_kasir}}
                </td>
                <td>
                    Tenant: {{$nama_tenant}}
                </td>
            </tr>
            <tr>
                <td>
                    Waktu Buka: {{$waktu_buka}}
                </td>
                <td>
                    Waktu Tutup: {{$waktu_tutup}}
                </td>
            </tr>
            <tr>
                <td>
                    Rest Area: {{$rest_area_name}}
                </td>
                <td>
                    <!-- No. Invoice -->
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table class="table">
        <thead>
            <tr>
                <th>Metode Pembayaran</th>
                <th>Nominal</th>
                <th colspan="2">Detail</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="6">Pembayaran Tunai</td>
                <td rowspan="6">@rp($pembayaran_tunai)</td>
            </tr>
            <tr>
                <td>Modal Uang Kembalian</td>
                <td>@rp($uang_kembalian)</td>
            </tr>
            <tr>
                <td>Nominal Uang Tunai</td>
                <td>@rp($uang_tunai)</td>
            </tr>
            <tr>
                <td>Selisih Tunai</td>
                <td>@rp($selisih_tunai)</td>
            </tr>
            <tr>
                <td>Nominal Koreksi</td>
                <td>@rp($nominal_koreski)</td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>{{$keterangan}}</td>
            </tr>
            <tr>
                <td rowspan="2">Pembayaran QR</td>
                <td rowspan="2">@rp($pembayaran_qr)</td>
            </tr>
            <tr>
                <td>Saldo tersimpan</td>
                <td></td>
            </tr>
            <tr>
                <td rowspan="3">Pembayaran Digital</td>
                <td rowspan="3">@rp($pembayaran_digital)</td>
            </tr>
            <tr>
                <td>BRI Virtual Account</td>
                <td>@rp($bri_va)</td>
            </tr>
            <tr>
                <td>Mandiri Virtual Account</td>
                <td>@rp($mandiri_va)</td>
            </tr>
        </tbody>
    </table>

    <div class="page_break"></div>

    <table>
        <tbody>
            <tr>
                <td>
                    <img style="width: 200px;" src="{{ public_path().'/img/tavsir-logo.png' }}" alt="logo">
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Detail Transaksi</td>
            </tr>
        </tbody>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Waktu Transaksi</th>
                <th>ID Transaksi</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Jenis Transkasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order as $value)
            <tr class="nowrap">
                <td>{{$loop->iteration}}</td>
                <td>{{$value['waktu_transaksi']}}</td>
                <td>{{$value['id_transaksi']}}</td>
                <td class="text-center">{{$value['total_product']}} item</td>
                <td class="text-right">@rp($value['total'])</td>
                <td>{{$value['metode_pembayaran']}}</td>
                <td>{{$value['jenis_transaksi']}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>