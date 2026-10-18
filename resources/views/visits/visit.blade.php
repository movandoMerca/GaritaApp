@extends('layout.default')


@section('styles')
    <!-- select2-bootstrap4-theme -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css">
    <!-- for live demo page -->
@endsection
@section('content')
    <form class="form" id="vehicle-visit-form"
        data-save-url="{{ route('save.visits') }}"
        data-session-url="{{ route('session.visits') }}"
        data-resident-url="{{ route('detail_ajax.resident') }}"
        data-detail-template="{{ route('detailv.visits', ['id' => '__VISIT_ID__']) }}"
        data-user-id="{{ Auth()->id() }}"
        data-submission-id="{{ (string) \Illuminate\Support\Str::uuid() }}"
        data-csrf-token="{{ csrf_token() }}"
        data-login-url="{{ route('login') }}"
        data-camera-licencia="{{ Auth()->user()->camara_id_licencia }}"
        data-camera-placa="{{ Auth()->user()->camara_id_placa }}"
        data-camera-visitante="{{ Auth()->user()->camara_id_visitante }}"
        data-photo-licencia="@json($config->enable_fotolicencia == 1)"
        data-photo-placa="@json(($config->enable_fotoplaca ?? 1) == 1)"
        data-photo-visitante="@json($config->enable_fotovisitante == 1)">
        <div class="card">
            <div class="card card-custom  gutter-b">
                <div class="card-body">
                    <div class="container">
                        <div id="responset" role="alert" aria-live="assertive" tabindex="-1" hidden></div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="symbol symbol-50 symbol-lg-120 symbol-light-primary">
                                    <span id="iniciales" class="font-size-h1 symbol-label font-weight-boldest"></span>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
                                    <div class="mr-3">
                                        <a
                                            class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                            <span>
                                                <label id="1nombre"></label>
                                                <label id="2nombre"></label>
                                                <label id="1apellido"></label>
                                                <label id="2apellido"></label>
                                            </span>
                                            <i class="flaticon2-correct text-success icon-md ml-2" id="icon"
                                                hidden></i>
                                        </a>
                                        <div class="d-flex flex-wrap my-2">
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="fas fa-calendar-day icon-l text-primary"></i></span>
                                                <label class="text-dark" id="fechanac"> </label>
                                            </a>
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="far fa-address-card icon-l text-primary"></i></span>
                                                <label class="text-dark" id="cui"> </label>
                                            </a>
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="fas fa-ambulance icon-l text-primary"></i></span>
                                                <label class="text-dark" id="telefonoEmergencia"> </label>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 mr-7" id="ocultar">
                                        <label>Codigo</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text input-group-solid bg-primary">
                                                    <i class="fas fa-barcode text-white"></i>
                                                </span>
                                            </div>
                                            <input type="text" id="data" name="data" class="form-control visit-input"
                                                autofocus />
                                            <div class="input-group-append">
                                                <button type="button" id="abrirIngresoManual"
                                                    class="btn btn-light-primary font-weight-bolder visit-input"
                                                    data-toggle="modal" data-target="#modalimg">
                                                    Ingreso manual
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center flex-wrap justify-content-between">
                                    <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                        <div class="row">
                                            <div class="col-md">
                                                <label>Acceso Telefonico</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i class="fa fa-phone text-white"></i>
                                                        </span>
                                                    </div>
                                                    <select id="selectResidente" class="form-control p-7 visit-input">
                                                        <option></option>
                                                        @foreach ($residents as $resident)
                                                            <option value="{{ $resident->id }}">
                                                                {{ $resident->accesotel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md" @if ($config->enable_accesotel != 1) hidden @endif>
                                                <label>No. Casa</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i
                                                                class="fa
                                                                fa-user-circle text-white"></i>
                                                        </span>
                                                    </div>
                                                    <input id="ftelefono" disabled type="text"
                                                        class="form-control input-sm" />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Placa</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i class="fas fa-car text-white"></i>
                                                        </span>
                                                    </div>
                                                    <input id="placa" type="text" maxlength="8" class="form-control visit-input" />
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <label>Cono</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i class="far fa-flag text-white"></i>
                                                        </span>
                                                    </div>
                                                    <input id="cono" type="text" maxlength="20" class="form-control input-sm visit-input" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mt-4">
                                            <label for="es_repartidor">Es repartidor</label>
                                            <span class="switch switch-sm switch-icon">
                                                <label>
                                                    <input type="checkbox" id="es_repartidor" class="visit-input" role="switch" aria-label="Es repartidor">
                                                    <span></span>
                                                </label>
                                            </span>
                                        </div><br>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Dirección</label>
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i class="fas fa-house-user text-white"></i>
                                                        </span>
                                                    </div>
                                                    <input id="direccion" type="text" class="form-control" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><br>
                        <div class="separator separator-solid my-7"></div>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="d-flex align-items-center flex-lg-fill mr-5 my-1">
                                <span class="mr-4">
                                    <i class="far fa-id-card  icon-xl text-primary"></i>
                                </span>
                                <div class="d-flex flex-column text-dark-75">
                                    <span class="font-weight-bolder font-size-sm">Tipo</span>
                                    <span class="font-weight-bolder font-size-h5" id="tipo">
                                        <span class="text-dark-50 font-weight-bold"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-lg-fill mr-5 my-1">
                                <span class="mr-4">
                                    <i class="fab fa-orcid icon-xl text-primary"></i>
                                </span>
                                <div class="d-flex flex-column text-dark-75">
                                    <span class="font-weight-bolder font-size-sm">Numero de documento</span>
                                    <span class="font-weight-bolder font-size-h5" id="ndocument">
                                        <span class="text-dark-50 font-weight-bold"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-lg-fill mr-5 my-1">
                                <span class="mr-4">
                                    <i class="far fa-calendar-times icon-xl text-primary"></i>
                                </span>
                                <div class="d-flex flex-column text-dark-75">
                                    <span class="font-weight-bolder font-size-sm">Fecha de Expiración</span>
                                    <span class="font-weight-bolder font-size-h5" id="fechavec">
                                        <span class="text-dark-50 font-weight-bold"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-lg-fill mr-5 my-1">
                                <span class="mr-4">
                                    <i class="far fa-clock icon-xl text-primary"></i>
                                </span>
                                <div class="d-flex flex-column flex-lg-fill">
                                    <span class="text-dark-75 font-weight-bolder font-size-sm">Ingreso</span>
                                    <span class="font-weight-bolder font-size-h5" id="horaentrada">
                                        <span class="text-dark-50 font-weight-bold"></span>
                                    </span>
                                </div>
                            </div>
                        </div><br><br>
                        <div class="row">
                            <div class="col-sm-4" @if ($config->enable_fotolicencia != 1) hidden @endif>
                                <span><b> Licencia </b></span> <br>
                                <video id="video1" width="320" height="240" autoplay muted playsinline></video>
                                <canvas hidden id="canvas1" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4" @if (($config->enable_fotoplaca ?? 1) != 1) hidden @endif>
                                <span><b><b>Placa</b></span> <br>
                                <video id="video2" width="320" height="240" autoplay muted playsinline></video>
                                <canvas hidden id="canvas2" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4" @if ($config->enable_fotovisitante != 1) hidden @endif>
                                <span><b>Visitante</b></span> <br>
                                <video id="video3" width="320" height="240" autoplay muted playsinline></video>
                                <canvas hidden id="canvas3" width="320" height="240"></canvas>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 d-flex justify-content-center">
                                <button type="button" id="capture-btn"
                                    class="btn btn-sm btn-light-info font-weight-bolder text-uppercase mr-2 visit-input">Tomar
                                    foto</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 my-lg-0 my-1 text-right">
                                <button type="submit" id="guardar"
                                    class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">Guardar</button>
                                <a href="{{ route('home') }}"
                                    class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase"
                                    id="prueba">Cancelar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" data-backdrop="static"
        aria-labelledby="myLargeModalLabel" id="modalimg" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header custom-bg text-white ">Ingreso Manual</div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <span>Nombre del visitante</span>
                            <input type="text" class="form-control visit-input" id="nombre-manual" maxlength="30"
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="text-center my-12">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="guardarManual" class="btn btn-primary visit-input"> Guardar </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/vehicle-visit.js') }}?v={{ filemtime(public_path('js/vehicle-visit.js')) }}"></script>
@endsection
