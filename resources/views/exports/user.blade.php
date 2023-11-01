<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h4>User</h4>
    <br>
    <table>
        <thead>
            <tr>
                <th>Nomor</th>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Business ID</th>
                <th>Business Name</th>
                <th>Merchant ID</th>
                <th>Sub Merchant ID</th>
                <th>Tenant ID</th>
                <th>Tenant Name</th>
                <th>Super Tenant ID</th>
                <th>Rest Area ID</th>
                <th>Rest Area Name</th>
                <th>Email Verified At</th>
                <th>Reset PIN</th>
                <th>Status</th>
                <th>Creadted At</th>
                <th>Is Subscription</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record as $value)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $value->id }}</td>
                <td>{{ $value->name }}</td>
                <td>{{ $value->email}}</td>
                <td>{{ $value->role }}</td>
                <td>{{ $value->business_id }}</td>
                <td>{{ $value->business->name ?? '' }}</td>
                <td>{{ $value->merchant_id }}</td>
                <td>{{ $value->sub_merchant_id }}</td>
                <td>{{ $value->tenat_id }}</td>
                <td>{{ $value->tenat->name ?? '' }}</td>
                <td>{{ $value->supertenat_id }}</td>
                <td>{{ $value->rest_area_id }}</td>
                <td>{{ $value->rest_area->name ?? '' }}</td>
                <td>{{ $value->email_verified_at }}</td>
                <td>{{ $value->reset_pin }}</td>
                <td>{{ $value->status }}</td>
                <td>{{ $value->created_at }}</td>
                <td>{{ $value->is_subscription }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>