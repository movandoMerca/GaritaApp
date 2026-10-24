@extends('layout.default')


@section('styles')
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2-bootstrap4.min.css') }}">
    <style>
        .resident-select-group { flex-wrap: nowrap; align-items: stretch; }
        .resident-select-group > .input-group-prepend { flex: 0 0 auto; }
        .resident-select-group .input-group-text {
            min-width: 2.5rem;
            padding: 0 .75rem;
            align-items: center;
            justify-content: center;
        }
        .resident-select-group > .select2-container {
            flex: 1 1 0;
            width: 0 !important;
            min-width: 0;
        }
        .resident-select-group .select2-selection--single {
            position: relative;
            display: flex;
            align-items: center;
            height: 2.5rem !important;
            font-size: .925rem;
            border-color: #e4e6ef;
        }
        .resident-select-group .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: .75rem;
            padding-right: 3.25rem;
            line-height: 1.35 !important;
        }
        .resident-select-group .select2-selection--single .select2-selection__arrow {
            top: 0;
            right: .5rem;
            height: 100%;
            width: 1rem;
        }
        .resident-select-group .select2-selection--single .select2-selection__arrow b { top: 50%; }
        .resident-select-group .select2-selection--single .select2-selection__clear {
            position: absolute;
            top: 50%;
            right: 1.9rem;
            float: none;
            width: 1rem;
            height: 1rem;
            padding: 0;
            margin: 0;
            transform: translateY(-50%);
            font-size: 1.15rem;
            line-height: 1;
            color: #7e8299;
            background: transparent;
        }
        .resident-select-group .select2-selection--single .select2-selection__clear:hover { color: #3699ff; background: transparent; }
    </style>
@endsection
@section('content')
    @livewire('vehicle-visit')
@endsection

@section('scripts')
    <script src="{{ asset('js/vehicle-visit.js') }}?v={{ filemtime(public_path('js/vehicle-visit.js')) }}"></script>
    <script src="{{ asset('js/vehicle-visit-camera.js') }}?v={{ filemtime(public_path('js/vehicle-visit-camera.js')) }}"></script>
    @livewireScripts
@endsection
