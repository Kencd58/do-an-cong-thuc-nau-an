@extends('layouts.app')
@section('title', 'Đăng ký')
@section('content')
<section class="auth-layout" aria-labelledby="page-title">
    <div class="auth-story">
        <p class="eyebrow">Góc bếp của bạn</p>
        <h1 id="page-title">Món ngon bắt đầu<br>từ <em>căn bếp nhỏ.</em></h1>
        <p>Lưu lại cảm hứng, chia sẻ công thức và mang những bữa ăn ngon đến gần nhau hơn.</p>
        <div class="story-note"><p>Mỗi công thức là một câu chuyện.<br>Câu chuyện của bạn bắt đầu ở đây.</p></div>
    </div>
    <div class="auth-card">
        <p class="eyebrow">Chào mừng đến Bếp Nhà</p>
        <h2>Tạo tài khoản</h2>
        <p class="card-intro">Cùng nhau nấu những điều thật ngon.</p>
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="field">
                <label for="name">Họ và tên</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required minlength="2" maxlength="100" placeholder="Tên của bạn" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name') <p class="field-error" id="name-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="254" placeholder="ban@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p class="field-error" id="email-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" aria-describedby="password-hint @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                <p class="field-hint" id="password-hint">Sử dụng ít nhất 8 ký tự.</p>
                @error('password') <p class="field-error" id="password-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8">
            </div>
            <button class="button button-full" type="submit">Tạo tài khoản</button>
        </form>
        <p class="form-switch">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </div>
</section>
@endsection
