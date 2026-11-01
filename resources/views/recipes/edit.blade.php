@extends('layouts.app')
@section('title', 'Sửa công thức')
@section('content')
    <div class="form-heading"><p class="eyebrow">GÓC BẾP RIÊNG</p><h1>Sửa công thức</h1><p>Các thay đổi sẽ hiển thị công khai sau khi lưu thành công.</p></div>
    <form class="recipe-form" data-recipe-form method="POST" action="{{ route('recipes.update', $recipe) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('recipes._form')
    </form>
@endsection
