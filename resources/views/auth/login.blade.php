@extends('layouts.app')
@section('title', 'Đăng nhập')
@section('content')
<section class="auth-layout" aria-labelledby="page-title">
    <div class="auth-story">
        <p class="eyebrow">Hôm nay, mình nấu gì?</p>
        <h1 id="page-title">Trở về bếp.<br>Thêm chút <em>yêu thương.</em></h1>
        <p>Một bữa cơm ngon không cần cầu kỳ. Chỉ cần một công thức hay và người cùng thưởng thức.</p>
        <div class="story-note"><p>Căn bếp quen, cảm hứng mới.<br>Rất vui được gặp lại bạn.</p></div>
    </div>
    <div class="auth-card">
        <p class="eyebrow">Chào bạn trở lại</p>
        <h2>Đăng nhập</h2>
        <p class="card-intro">Tiếp tục câu chuyện trong căn bếp của bạn.</p>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required maxlength="254" placeholder="ban@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p class="field-error" id="email-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password') <p class="field-error" id="password-error">{{ $message }}</p> @enderror
            </div>
            <button class="button button-full" type="submit">Đăng nhập</button>
        </form>
        <p class="form-switch">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></p>
    </div>
</section>
@endsection
