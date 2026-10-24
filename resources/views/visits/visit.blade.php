@extends('layout.default')


@section('styles')
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2-bootstrap4.min.css') }}">
@endsection
@section('content')
    @livewire('vehicle-visit')
@endsection

@section('scripts')
    <script src="{{ asset('js/vehicle-visit.js') }}?v={{ filemtime(public_path('js/vehicle-visit.js')) }}"></script>
    <script src="{{ asset('js/vehicle-visit-camera.js') }}?v={{ filemtime(public_path('js/vehicle-visit-camera.js')) }}"></script>
    @livewireScripts
@endsection
