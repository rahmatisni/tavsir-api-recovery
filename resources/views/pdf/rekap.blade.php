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
</style>
<body>
    <table>
        <tbody>
            <tr>
                <td>
                    <img style="width: 200px;" src="{{ asset('/img/tavsir-logo.png') }}" alt="logo">
                </td>
                <td>Perode : {{$record->periode}}</td>
            </tr>
            <tr>
                <td colspan="2">Rekap Pendapatan</td>
            </tr>
            <tr>
                <td>
                    Kasir
                </td>
                <td>
                    Tenant
                </td>
            </tr>
            <tr>
                <td>{{$record->cashier->name ?? ''}}</td>
                <td>{{$record->tenant->name ?? ''}}</td>
            </tr>
            <tr>
                <td>
                    Waktu Buka
                </td>
                <td>
                    Waktu Tutup
                </td>
            </tr>
            <tr>
                <td>
                    {{$record->start_date}}
                </td>
                <td>
                    {{$record->end_date}}
                </td>
            </tr>
            <tr>
                <td>
                    Rest Area
                </td>
                <td>
                    <!-- No. Invoice -->
                </td>
            </tr>
            <tr>
                <td>
                    {{$record->tenant->rest_area->name ?? ''}}
                </td>
                <td>
                    <!-- INV-XXXX -->
                </td>
            </tr>
        </tbody>
    </table>
    <table style="width: 100%;">
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
                <td rowspan="6">@rp($record->trans_cashbox->rp_cash)</td>
            </tr>
            <tr>
                <td>Modal Uang Kembalian</td>
                <td>@rp($record->trans_cashbox->initial_cashbox)</td>
            </tr>
            <tr>
                <td>Nominal Uang Tunai</td>
                <td>@rp($record->trans_cashbox->cashbox)</td>
            </tr>
            <tr>
                <td>Selisih Tunai</td>
                <td>@rp($record->trans_cashbox->different_cashbox)</td>
            </tr>
            <tr>
                <td>Nominal Koreksi</td>
                <td>@rp($record->trans_cashbox->pengeluaran_cashbox)</td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>{{$record->trans_cashbox->description}}</td>
            </tr>
            <tr>
                <td rowspan="2">Pembayaran QR</td>
                <td rowspan="2">@rp($record->trans_cashbox->rp_tav_qr)</td>
            </tr>
            <tr>
                <td>Saldo tersimpan</td>
                <td></td>
            </tr>
            <tr>
                <td rowspan="3">Pembayaran Digital</td>
                <td rowspan="3">@rp($record->trans_cashbox->total_digital())</td>
            </tr>
            <tr>
                <td>BRI Virtual Account</td>
                <td>@rp($record->trans_cashbox->rp_va_bri)</td>
            </tr>
            <tr>
                <td>Mandiri Virtual Account</td>
                <td>@rp($record->trans_cashbox->rp_va_mandiri)</td>
            </tr>
        </tbody>
    </table>

    <div class="page_break"></div>

    <table>
        <tbody>
            <tr>
                <td>
                    <img style="width: 200px;" src="{{ asset('/img/tavsir-logo.png') }}" alt="logo">
                </td>
            </tr>
            <tr>
                <td >Detail Transaksi</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Waktu Transaksi</th>
                <th>ID Transaksi</th>
                <th>Total Product</th>
                <th>Total</th>
                <th>Metode Pembayaran</th>
                <th>Jenis Transkasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order as $value)
            <tr>
                <td>{{$value->created_at}}</td>
                <td>{{$value->order_id}}</td>
                <td>{{$value->detil->count()}} item</td>
                <td style="white-space: nowrap;">@rp($value->total)</td>
                <td>{{$value->payment_method->name ?? ''}}</td>
                <td>{{$value->labelOrderType()}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>