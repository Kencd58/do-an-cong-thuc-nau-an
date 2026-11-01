@extends('layouts.app')
@section('title', 'Tạo công thức')
@section('content')
    <div class="form-heading"><p class="eyebrow">CHIA SẺ TỪ CĂN BẾP</p><h1>Tạo công thức</h1><p>Công thức sẽ được công khai ngay khi bạn lưu thành công.</p></div>
    <form class="recipe-form" data-recipe-form method="POST" action="{{ route('recipes.store') }}" enctype="multipart/form-data">
        @csrf
        @include('recipes._form')
    </form>
@endsection
