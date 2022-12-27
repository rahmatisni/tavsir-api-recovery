@extends('email._template')

@section('content')
<p>Halo kak {{ $user_name }} &#128522</p>
<p>
    <b>
        Selamat, Pembayaran dengan ID Transaksi {{ $order->order_id }} di {{ $order->tenant->name ?? 'Tenant' }} - {{ $order->tenant->rest_area->name ?? 'Rest area' }} berhasil!
    </b>
</p>
<p>Terima kasih telah berbelanja di toko kami. Semoga harimu selalu menyenangkan ya! Berikut struk digital kamu.</p>
<p>
    <center>
        {{-- <img src="http://172.16.4.47:3007/api/image-upload/TNG-20220823091824" alt="TNG-20220823091824"> --}}
        <img src="{{ $path_image }}" alt="">
    </center>
</p>
<p>Jangan lupa kasih rating untuk toko kami di aplikasi Travoy.</p>
<p>Download aplikasi Travoy untuk transaksi yang lebih mudah dan cepat.</p>
<br>
<p>Follow akun official Jasa Marga untuk mengetahui informasi lainnya.</p>
<p>Twitter: @PT.JASAMARGA</p>
<p>Instagram: @official.jasamarga</p>
<p>Website: www.jasamarga.com</p>
@endsection