@extends('layouts.app')
@section('title', 'Bếp Nhà')
@section('content')
<section class="welcome" aria-labelledby="page-title">
    <p class="eyebrow">CẢM HỨNG TỪ NHỮNG ĐIỀU GIẢN DỊ</p>
    @auth
        <h1 id="page-title">Chào {{ auth()->user()->name }},<br>mừng bạn về <em>Bếp Nhà.</em></h1>
        <p>Bạn đã đăng nhập. Chuyên mục công thức đang được chuẩn bị.</p>
    @else
        <h1 id="page-title">Bữa ngon mỗi ngày.<br>Từ chính <em>bếp nhà.</em></h1>
        <p>Một nơi để chia sẻ công thức và niềm vui nấu nướng.<br>Hãy bắt đầu bằng tài khoản của riêng bạn.</p>
        <div class="welcome-actions"><a class="button" href="{{ route('register') }}">Tham gia Bếp Nhà <span aria-hidden="true">→</span></a><a href="{{ route('login') }}">Tôi đã có tài khoản</a></div>
    @endauth
</section>
@endsection
