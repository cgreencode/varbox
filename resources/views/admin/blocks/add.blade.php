@extends('varbox::layouts.default')

@section('title', $title)

@section('content')
    @include('varbox::admin.blocks._form', ['url' => route('admin.blocks.store')])
@endsection
