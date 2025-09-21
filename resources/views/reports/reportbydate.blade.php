@extends('layout.default')

@section('content')
    <!--begin::Card-->
    <div class="card card-custom gutter-b border custom-b">
        <div class="card-header flex-wrap border-0 pt-6 pb-0 custom-bg ">
            <div class="card-title">
                <h2 class="card-label text-white">Reporte de Visitas</h2>
                <input type="hidden" name="logo" id="logo" value="{{ $image }}">
            </div>
        </div>
        <div class="card-body">
            <!--begin: Datatable-->
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered nowrap text-center" style="" id="visitreport">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Ingreso</th>
                                @if ($config->enable_egreso==1)<th>Egreso</th>@endif
                                <th>Tipo</th>
                                <th>DPI</th>
                                <th>Visitante</th>
                                <th>Fecha de Nacimiento</th>
                                <th>Numero de documento</th>
                                <th>Fecha de expiracion</th>
                                <th>Placa</th>
                                <th>Cono</th>
                                <th>Codigo Residente</th>
                                <th>Nombre Residente</th>
                                {{-- @if ($config->enable_tel==1)<th>Telefono</th>@endif --}}
                                @if ($config->enable_accesotel==1)<th>Acceso Telefonico</th>@endif
                                <th>Dirección</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visits as $visit)
                            <tr>
                                <td>
                                    <a href="{{ route('detailegreso.visits', ['id' => $visit->id]) }}"
                                        class="btn btn-sm btn-clean btn-icon" title="Detalle de visita">
                                       <i class="fas fa-sign-out-alt"></i>
                                    </a>
                                </td>
                                <td>{{ $visit->fechaingreso }}</td>
                                @if ($config->enable_egreso==1)<td>{{ $visit->fechaegreso }}</td>@endif
                                <td>{{ $visit->tipoLicencia }}</td>
                                <td>{{ $visit->cui}}</td>
                                <td>{{ $visit->Primer_Nombre }} {{ $visit->Segundo_Nombre }} {{ $visit->Primer_Apellido }} {{ $visit->Segundo_Apellido }}</td>
                                <td>{{ $visit->Fecha_nac }}</td>
                                <td>{{ $visit->numeroDocumento }}</td>
                                <td>{{ $visit->Fecha_vencimiento }}</td>
                                <td>{{ $visit->Placa }}</td>
                                <td>{{ $visit->cono}}</td>
                                <td>{{ $visit->residente->Codigo }}</td>
                                <td>{{ $visit->residente->Nombres }} {{ $visit->residente->Apellidos }}</td>
                                {{-- @if ($config->enable_tel==1)<td>{{ $visit->residente->Telefono }}</td>@endif --}}
                                @if ($config->enable_accesotel==1)<td>{{ $visit->residente->accesotel }}</td>@endif
                                <td>{{ $visit->residente->Direccion }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    <a href="javascript: history.go(-1)"  class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/visitreport_table.js') }}">
    </script>
@endsection
