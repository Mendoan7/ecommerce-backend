@extends('mails.layout')

@section('content')
    Halo {{ $user->name }}, </br>

    Ini adalah Register OTP anda: {{ $user->otp_register }}
@endsection
