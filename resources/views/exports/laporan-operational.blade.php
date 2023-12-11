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
    <table style="text-align: center;">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Periode</th>
                <th rowspan="2">Waktu Buka</th>
                <th rowspan="2">Waktu Tutup</th>
                <th rowspan="2">Waktu Rekap</th>
                <th rowspan="2">Kasir</th>
                <th colspan="{{ $sharing_count }}">Bagi Hasil</th>
                <th rowspan="2">Nominal Uang Kembalian</th>
                <th rowspan="2">Total Pendapatan QR</th>
                <th rowspan="2">Total Pendapatan Pembayaran Digital</th>
                <th rowspan="2">Total Pendapatan Tunai</th>
                <th rowspan="2">Nominal Uang Tunai</th>
                <th rowspan="2">Total Biaya Tambahan</th>
                <th rowspan="2">Nominal Koreksi</th>
                <th rowspan="2">Selisih</th>
                <th rowspan="2">Keterangan Koreksi</th>
                <th rowspan="2">Total Nominal Rekap</th>
            </tr>
            <tr>
            @for ($i = 0; $i < $sharing_count; $i++)
                @if($i == 0)
                <th>Tenant</th>
                @else
                <th>Investor {{$i}}</th>
                @endif
            @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            @php
                $array = [];
                for($i = 1; $i <= $sharing_count; $i++){
                    $array[] = null;
                }
                $cek = $value?->trans_cashbox?->sharing ?? null;
                if($cek){
                    $array = json_decode($cek, true);
                }
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value->periode  }}</td>
                <td>{{ $value->waktu_buka }}</td>
                <td>{{ $value->waktu_tutup }}</td>
                <td>{{ $value->waktu_rekap }}</td>
                <td>{{ $value->kasir }}</td>
                @foreach($array as $k => $l)
                    <td>{{$k}} : {{$l}}</td>
                @endforeach
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
                <td colspan="{{$sharing_count + 7}}">Total</td>
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