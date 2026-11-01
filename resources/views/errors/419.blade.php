@extends('layouts.app')
@section('title', 'Phiên làm việc đã hết hạn')
@section('content')
<section class="error-page">
    <p class="eyebrow">419 · PHIÊN LÀM VIỆC ĐÃ HẾT HẠN</p>
    <h1>Vui lòng tải lại trang</h1>
    <p>Phiên làm việc đã hết hạn, vui lòng tải lại trang.</p>
    <a class="button" href="{{ url()->current() === route('register') ? route('register') : route('login') }}">Mở lại biểu mẫu</a>
</section>
@endsection
