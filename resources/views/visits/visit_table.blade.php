@extends('layout.default')

@section('content')


    <!--begin::Card-->
    <div class="card card-custom gutter-b border custom-b">
        <div class="card-header flex-wrap border-0 pt-6 pb-0 custom-bg ">
            <div class="card-title">
                <h2 class="card-label text-white">Visitas activas</h2>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url()->current() }}">
                @include('visits._filters', ['submit' => true])
            </form>
            <!--begin: Datatable-->
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered nowrap text-center" style="" id="visitreport_today">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Ingreso</th>
                                <th>Egreso</th>
                                <th>Cono</th>
                                <th>Tipo de visita</th>
                                <th>Repartidor</th>
                                <th>Tipo de licencia</th>
                                <th>DPI</th>
                                <th>Visitante</th>
                                <th>Fecha de Nacimiento</th>
                                <th>Numero de documento</th>
                                <th>Fecha de expiracion</th>
                                <th>Placa</th>
                                <th>Residente</th>
                                <th>Codigo</th>
                                <th>Acceso Telefonico</th>

                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($visits as $visit)
                            <tr>
                                <td>
                                    <a href="{{ route('detailegreso.visits', ['id' => $visit->id]) }}"
                                        class="btn btn-sm btn-clean btn-icon" title="Salida Visitante">
                                       <i class="fas fa-sign-out-alt"></i>
                                    </a>
                                </td>
                                <td>{{ $visit->fechaingreso }}</td>
                                <td>{{ $visit->fechaegreso }}</td>
                                <td>{{ $visit->cono }}</td>
                                <td>{{ $visit->isPedestrian() ? 'Peatonal' : 'Vehicular' }}</td>
                                <td>{{ $visit->es_repartidor ? 'Sí' : 'No' }}</td>
                                <td>{{ $visit->tipoLicencia ?: '—' }}</td>
                                <td>{{ $visit->cui}}</td>
                                <td>{{ $visit->Primer_Nombre }} {{ $visit->Segundo_Nombre }} {{ $visit->Primer_Apellido }} {{ $visit->Segundo_Apellido }}</td>
                                <td>{{ $visit->Fecha_nac }}</td>
                                <td>{{ $visit->numeroDocumento }}</td>
                                <td>{{ $visit->Fecha_vencimiento }}</td>
                                <td>{{ $visit->Placa }}</td>
                                <td>{{ $visit->residente->fullname (false)}}</td>
                                <td>{{ $visit->residente->Codigo }}</td>
                                <td>{{ $visit->residente->accesotel }}</td>

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
    <script>
        $('#visitreport_today').DataTable({ scrollX: true, order: [[1, 'desc']], language: { url: @json(asset('vendor/datatables/Spanish.json')) } });
    </script>
@endsection
