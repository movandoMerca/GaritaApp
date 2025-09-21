{{-- Extends layout --}}
@extends('layout.default')

{{-- Content --}}
@section('content')

    {{-- Dashboard 1 --}}

    <div class="row">
        <div class="col-md-4">
            @include('pages.widgets._widget-1', ['class' => 'card-stretch gutter-b border custom-b'])
        </div>

        <div class="col-md-8">
            @include('pages.widgets._widget-2', ['class' => 'card-stretch gutter-b border custom-b'])
        </div>



        
    </div>

@endsection

{{-- Scripts Section --}}
