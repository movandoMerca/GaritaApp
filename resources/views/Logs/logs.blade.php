
@extends('layout.default')

@section('content')


<div class="card card-custom gutter-b border custom-b">
    <div class="card-header flex-wrap border-0 pt-6 pb-0 custom-bg ">
        <div class="card-title">
            <h2 class="card-label text-white">LOGS</h2>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">

           
            <table class="table table-bordered nowrap text-center" style="" id="logs">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>usuario</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at}}</td>
                        <td>{{ $log->usuario->name}}</td>
                        <td>{{ $log->accion}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/logs.js') }}">
    </script>
@endsection