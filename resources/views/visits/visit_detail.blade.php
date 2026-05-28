@extends('layout.default')


@section('content')
    <div class="card">
        <div class="card card-custom gutter-b">
            <div class="card-body">
                <div class="row ">
                    <div class=" text-center col-md-12">
                        <div class="symbol symbol-circle symbol-100 symbol-lg-100">
                            @if ($config->enable_fotovisitante == 1)
                                @if ($visits->path_visitante != null)
                                    <br> <img src="{{ route('img.visits', $visits->path_visitante) }}" alt="">
                                @endif
                            @endif
                        </div>
                        <br><br><span class="text-primary font-weight-bolder ">Visitante</span><br>
                        <a href="#" class="card-title text-hover-primary font-weight-bolder font-size-h5 text-dark mb-1">
                            {{ $visits->Primer_Nombre }}
                            {{ $visits->Segundo_Nombre }}
                            {{ $visits->Primer_Apellido }}
                            {{ $visits->Segundo_Apellido }}</a><br>
                        DPI: {{ $visits->cui }} <br>
                        Fecha Nacimiento: {{ $visits->Fecha_nac }}
                    </div>
                </div><br>
                <hr><br>
                <div class="row text-center">
                    <div class="col-md">
                        <span class="d-block font-weight-bold mb-2">Placa</span>
                        <span
                            class="btn btn-light-warning btn-sm font-weight-bold btn-upper btn-text">{{ $visits->Placa }}</span>
                    </div>
                    <div class="col-md">
                        <span class="d-block font-weight-bold mb-2">Tipo</span>
                        <span
                            class="btn btn-light-primary btn-sm font-weight-bold btn-upper btn-text">{{ $visits->tipoLicencia }}</span>
                    </div>
                    <div class="col-md">
                        <span class="d-block font-weight-bold mb-2">Documento</span>
                        <span
                            class="btn btn-light-primary btn-sm font-weight-bold btn-upper btn-text">{{ $visits->numeroDocumento }}</span>
                    </div>
                    <div class="col-md">
                        <span class="d-block font-weight-bold mb-2">Expiración</span>
                        <span
                            class="btn btn-light-primary btn-sm font-weight-bold btn-upper btn-text">{{ $visits->Fecha_vencimiento }}</span>
                    </div>
                    <div class="col-md">
                        <span class="d-block font-weight-bold mb-2">Ingreso</span>
                        <span
                            class="btn btn-light-success btn-sm font-weight-bold btn-upper btn-text">{{ $visits->fechaingreso }}</span>
                    </div>
                </div><br>
                <hr>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <span class="text-primary font-weight-bolder ">Residente</span><br>
                        <br>
                        <label class="">{{ $visits->residente->fullname(false) }}</label></span>
                        <br>
                        <label class="">{{ $visits->residente->Direccion }}</label></span>
                      
                        @if (auth()->user()->is_admin == 1)
                            @if ($config->enable_tel == 1)
                                <br> <span class=" font-weight-bold">Telefono:
                                    <label class="">{{ $visits->residente->Telefono }}</label></span>
                            @endif
                            @if ($config->enable_accesotel == 1)
                                <br> <span class=" font-weight-bold">Cod. Acceso Tel.:
                                    {{ $visits->residente->accesotel }}<label class="text-primary"></label></span>
                            @endif
                        @endif

                        <br> <span class=" font-weight-bold">Cono: {{ $visits->cono }}<label
                                class="text-primary"></label></span>

                    </div>
                </div><br>
                @if ($config->enable_fotolicencia == 1)
                    <h3>Licencia</h3>
                    <hr><br>
                    <div class="row text-center">
                        <div class="col-md-12">
                            <img src="{{ route('img.visits', $visits->path_licencia) }}" alt=""
                                style="max-width: 350px;">
                        </div>
                    </div><br><br>
                @endif
                @if (($config->enable_fotoplaca ?? 1) == 1)
                    <h3>Placa</h3>
                    <hr><br>
                    <div class="row text-center">
                        <div class="col-md-12">
                            @if ($visits->path_placa != null)
                                <img src="{{ route('img.visits', $visits->path_placa) }}" alt=""
                                    style="max-width: 350px;">
                            @endif
                        </div>
                    </div><br><br>
                @endif
     
                <div class="row text-center">
                    <div class="col-md-12 ">
                        <a href="{{ route('create.visits') }}"
                            class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">
                            Aceptar</a>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
