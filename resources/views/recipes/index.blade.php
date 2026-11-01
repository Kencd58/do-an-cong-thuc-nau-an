@extends('layouts.app')
@section('title', 'Tìm kiếm công thức')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Tìm món phù hợp với bạn</p><h1>Tìm kiếm công thức</h1><p>Chọn nguyên liệu, thời gian và khẩu vị phù hợp với bạn.</p></div>
        <a class="button" href="{{ route('recipes.create') }}">Chia sẻ công thức</a>
    </div>
    @include('recipes._filters')
    @include('recipes._list')
@endsection
