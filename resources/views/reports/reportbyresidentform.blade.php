@extends('layout.default')

@section('content')


    <div class="card card-custom  gutter-b border custom-b">
        <div class="card-header custom-bg ">
            <div class="card-title">
                <h3 class="card-label text-white">
                    Reporte de Visitas:
                    <small>Búsqueda por Residente</small>
                </h3>
            </div>
        </div>
        <div class="card-body">

            <form action="{{ route("reportbyresi.visits") }}" method="POST"  >
                @csrf
            <div class="row">
                <div class="col-md-4">
                    <label>Residente</label>
                    <div class="input-group input-group-sm">
                        <select id="selectResidente" name='selectResidente' class="form-control p-7 @error('id') is-invalid @enderror">                            
                            <option></option>
                            @foreach ($residents as $resident)
                                <option value="{{ $resident->id }}">
                                    {{ $resident->fullname(true) }}</option>
                            @endforeach
                        </select>                       
                    </div>
                    @error('selectResidente')
                    <div class="alert text-danger">{{ $message }}</div>
                     @enderror
                </div>
                <div class="col-md-4">
                    <label for="fechai">Fecha Inicial</label>
                    <input type="date" name="from" id="from" class="form-control input-sm @error('from') is-invalid @enderror">
                    @error('from')
                    <div class="alert text-danger">{{ $message }}</div>
                     @enderror
                </div>
                <div class="col-md-4">
                    <label for="fechaf">Fecha Final</label>
                    <input type="date" name="to" id="to" class="form-control input-sm @error('to') is-invalid @enderror " max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                    @error('to')
                    <div class="alert text-danger">{{ $message }}</div>
                     @enderror
                </div>
            </div><br>
            <div class="row">
                <div class="col-md-12 text-center">
                    <button type="submit"
                        class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">Buscar</button>
                    <a href="javascript: history.go(-1)"  class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
                </div>
            </div>

            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script src="{{ asset('js/reportbydate.js') }}">
</script>
@endsection