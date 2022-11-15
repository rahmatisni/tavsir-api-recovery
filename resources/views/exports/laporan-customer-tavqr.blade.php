<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h4>Customer TavQR</h4>
    <br>
    <h4>Tanggal Awal : {{ $tanggal_awal }}</h4>
    <h4>Tanggal Akhir : {{ $tanggal_akhir }}</h4>
    <h4>Rest Area : {{ $rest_area }}</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $key => $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value['customer_name'] }}</td>
                <td>{{ $value['customer_phone'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan=3>Total</td>
                <td>{{$record->count()}}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>