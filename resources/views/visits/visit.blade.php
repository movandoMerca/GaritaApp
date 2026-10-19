@extends('layout.default')


@section('styles')
    @livewireStyles
    <!-- select2-bootstrap4-theme -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css">
    <!-- for live demo page -->
@endsection
@section('content')
    @livewire('vehicle-visit')
@endsection

@section('scripts')
    <script src="{{ asset('js/vehicle-visit.js') }}?v={{ filemtime(public_path('js/vehicle-visit.js')) }}"></script>
    @livewireScripts
@endsection
