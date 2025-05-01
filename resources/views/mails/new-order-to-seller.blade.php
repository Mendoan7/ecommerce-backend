@extends('mails.layout')

@section('content')
    Hai seller {{ $order->seller->name }},<br> <br>
    Ada order baru nih dari {{ $order->user->name }}, silahkan cek aplikasi untuk melihat detail.
    <br>
    <br>
    @foreach ($order->items as $item)
        {{ $item->product->name }} x {{ $item->qty }}<br>
    @endforeach
@endsection
