@extends('layout.default')


@section('styles')
    @livewireStyles
    <style>
        .vehicle-manual-backdrop { position: fixed; inset: 0; z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(0, 0, 0, .55); }
        .vehicle-manual-dialog { width: min(100%, 640px); max-height: calc(100vh - 32px); overflow-y: auto; border-radius: 4px; background: #fff; box-shadow: 0 12px 36px rgba(0, 0, 0, .25); }
        .vehicle-manual-dialog .modal-title { margin: 0; font-size: 1.25rem; }
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
