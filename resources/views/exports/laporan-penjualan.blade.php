<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h4>Laporan Penjualan Product</h4>
    <br>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Product</th>
                <th>Kategori</th>
                <th>Jumlah Terjual</th>
                <th>Harga Product</th>
                <th>Pendapatan Product</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{ $value->product->sku ?? '' }}</td>
                <td>{{ $value->product->name ?? '' }}
                    @foreach($value->customize as $v)
                    {!! $v->customize_name.': '.$v->pilihan_name !!}
                    @endforeach</td>
                <td>{{ $value->product->category->name ?? '' }}</td>
                <td>{{ $value->qty }}</td>
                <td>{{ $value->base_price }}</td>
                <td>{{ $value->total_price }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ $record->sum('qty')}}</td>
                <td></td>
                <td>{{ $record->sum('total_price')}}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>