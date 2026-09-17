@extends('layouts.app')

@section('content')
    @include('pages.common.dynamic-service-content', ['orderRoute' => 'teknisi.pesan'])
@endsection
