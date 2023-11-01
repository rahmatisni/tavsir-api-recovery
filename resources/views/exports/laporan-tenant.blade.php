<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h4>Rest Area Venue</h4>
    <br>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tenant</th>
                <th>Rest Area</th>
                <th>Tenant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $key => $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value->tenant->name ?? '' }}</td>
                <td>{{ $value->rest_area->name ?? '' }}</td>
                <td>{{ $value->name }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan=2>Total</td>
                <td>{{$record->count()}}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>