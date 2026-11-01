@extends('layouts.app')
@section('title', 'Trang chủ')
@section('content')
    <div class="page-heading home-heading">
        <div>
            <p class="eyebrow">Cộng đồng Bếp Nhà</p>
            <h1>Cùng nhau vào bếp</h1>
            <p>Khám phá những công thức vừa được chia sẻ từ cộng đồng Bếp Nhà.</p>
        </div>
        <a class="button" href="{{ route('recipes.create') }}">Chia sẻ công thức</a>
    </div>
    <section class="community-feed" aria-labelledby="feed-title">
        <div class="feed-heading">
            <h2 id="feed-title">Công thức mới chia sẻ</h2>
            <span>{{ number_format($recipes->total()) }} công thức · Mới nhất trước</span>
        </div>
        @include('recipes._list')
    </section>
@endsection
