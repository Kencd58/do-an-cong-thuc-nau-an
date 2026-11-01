@extends('layouts.app')
@section('title', 'Công thức của tôi')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Góc bếp của bạn</p><h1>Công thức của tôi</h1><p>Các món ăn bạn đã chia sẻ với mọi người.</p></div>
        <a class="button" href="{{ route('recipes.create') }}">Tạo công thức</a>
    </div>
    @include('recipes._filters')
    @include('recipes._list')
@endsection
