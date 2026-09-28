@extends('layouts.app')

@section('content')
    @include('pages.common.dynamic-service-content', [
        'orderRoute' => $orderRoute ?? 'layanan.pesan',
        'orderRouteParams' => $orderRouteParams ?? ['slug' => $slug ?? ($layanan->clean_slug ?? '')]
    ])
@endsection
