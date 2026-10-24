<div data-vehicle-visit-component data-user-id="{{ $ownerId }}" data-submission-id="{{ $submissionId }}" data-modal-open="{{ $modalOpen ? 'true' : 'false' }}">
    <form wire:submit.prevent="save" class="form" id="vehicle-visit-form" autocomplete="off"
        data-livewire-url="{{ route('livewire.message', ['name' => 'vehicle-visit'], false) }}"
        data-session-url="{{ route('session.visits', [], false) }}"
        data-user-id="{{ Auth()->id() }}"
        data-submission-id="{{ $submissionId }}"
        data-csrf-token="{{ csrf_token() }}"
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
                        <div id="responset" role="alert" aria-live="assertive" tabindex="-1"
                            class="{{ $errors->any() ? 'alert alert-danger' : ($notice ? 'alert alert-info' : '') }}"
                            @if (!$errors->any() && !$notice) hidden @endif>
                            @if ($errors->any())
                                <div>Revise estos datos antes de guardar:</div>
                                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            @else
                                {{ $notice }}
                            @endif
                        </div>
                        <fieldset class="border-0 p-0 m-0 w-100" @if ($status !== 'editing') disabled @endif wire:loading.attr="disabled" wire:target="save,recover">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="symbol symbol-50 symbol-lg-120 symbol-light-primary">
                                    <span id="iniciales" class="font-size-h1 symbol-label font-weight-boldest">{{ $initials }}</span>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <div class="d-flex align-items-center justify-content-between flex-wrap mt-2">
                                    <div class="mr-3">
                                        <a
                                            class="d-flex align-items-center text-dark text-hover-primary font-size-h5 font-weight-bold mr-3">
                                            <span>
                                                <label id="1nombre">{{ $visitorNames[0] }}</label>
                                                <label id="2nombre">{{ $visitorNames[1] }}</label>
                                                <label id="1apellido">{{ $visitorNames[2] }}</label>
                                                <label id="2apellido">{{ $visitorNames[3] }}</label>
                                            </span>
                                            <i class="flaticon2-correct text-success icon-md ml-2" id="icon"
                                                @if (!$visitor && $nombreManual === '') hidden @endif></i>
                                        </a>
                                        <div class="d-flex flex-wrap my-2">
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="fas fa-calendar-day icon-l text-primary"></i></span>
                                                <label class="text-dark" id="fechanac">{{ $visitor['Fecha_nac'] ?? '' }}</label>
                                            </a>
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="far fa-address-card icon-l text-primary"></i></span>
                                                <label class="text-dark" id="cui">{{ $visitor['cui'] ?? '' }}</label>
                                            </a>
                                            <a href="#"
                                                class="text-muted text-hover-primary font-weight-bold mr-lg-8 mr-5 mb-lg-0 mb-2">
                                                <span><i class="fas fa-ambulance icon-l text-primary"></i></span>
                                                <label class="text-dark" id="telefonoEmergencia">{{ $visitor['tel_emergencia'] ?? '' }}</label>
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
                                            <input wire:model.defer="codigoLicencia" wire:keydown.enter.prevent="scanLicense" type="text" id="data" name="data" class="form-control visit-input"
                                                autocomplete="off" autocapitalize="off" spellcheck="false" autofocus />
                                            <div class="input-group-append">
                                                <button type="button" id="abrirIngresoManual"
                                                    class="btn btn-light-primary font-weight-bolder visit-input"
                                                    wire:click="openManual">
                                                    Ingreso manual
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center flex-wrap justify-content-between">
                                    <div class="flex-grow-1 font-weight-bold text-dark-50 py-2 py-lg-2 mr-5">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label for="selectResidente">Acceso telefónico / residente</label>
                                                <div class="input-group input-group-sm resident-select-group" wire:ignore>
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text input-group-solid bg-primary">
                                                            <i class="fa fa-phone text-white"></i>
                                                        </span>
                                                    </div>
                                                    <select data-livewire-input="residente" id="selectResidente" class="form-control visit-input">
                                                        <option value=""></option>
                                                        @foreach ($residents as $resident)
                                                            <option value="{{ $resident->id }}" @if ((string) $residente === (string) $resident->id) selected @endif>
                                                                {{ $resident->accesotel }} | {{ $resident->Codigo }} | {{ $resident->fullname(false) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @if (is_string($residentSearch) && trim($residentSearch) !== '' && $residents->isEmpty())
                                                    <small class="text-muted d-block mt-1">No se encontraron residentes activos.</small>
                                                @endif
                                                @error('residente') <div class="text-danger mt-1">{{ $message }}</div> @enderror
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
                                                    <input wire:loading.class="invisible" wire:target="residente" value="{{ $houseCode }}" id="ftelefono" disabled type="text"
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
                                                    <input wire:model.defer="placa" id="placa" type="text" maxlength="8" class="form-control visit-input" />
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
                                                    <input wire:model.defer="cono" id="cono" type="text" maxlength="20" class="form-control input-sm visit-input" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mt-4">
                                            <label for="es_repartidor">Es repartidor</label>
                                            <span class="switch switch-sm switch-icon">
                                                <label>
                                                    <input wire:model.defer="es_repartidor" type="checkbox" id="es_repartidor" class="visit-input" role="switch" aria-label="Es repartidor">
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
                                                    <input wire:loading.class="invisible" wire:target="residente" value="{{ $address }}" id="direccion" type="text" class="form-control" disabled />
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
                                        {{ $visitor['tipoLicencia'] ?? '' }}
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
                                        {{ $visitor['numeroDocumento'] ?? '' }}
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
                                        {{ $visitor['Fecha_vencimiento'] ?? '' }}
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
                                        {{ $scannedAt }}
                                    </span>
                                </div>
                            </div>
                        </div><br><br>
                        <div class="row">
                            <div class="col-sm-4" @if ($config->enable_fotolicencia != 1) hidden @endif>
                                <span><b> Licencia </b></span> <br>
                                <div @if (isset($photoVersions['licencia'])) hidden @endif>
                                    <video wire:ignore id="video1" width="320" height="240" autoplay muted playsinline></video>
                                </div>
                                @if (isset($photoVersions['licencia']))
                                    <img id="photo-preview-licencia" src="{{ $this->photoUrl('licencia') }}" width="320" height="240" alt="Foto de licencia">
                                @endif
                                <canvas wire:ignore hidden id="canvas1" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4" @if (($config->enable_fotoplaca ?? 1) != 1) hidden @endif>
                                <span><b><b>Placa</b></span> <br>
                                <div @if (isset($photoVersions['placa'])) hidden @endif>
                                    <video wire:ignore id="video2" width="320" height="240" autoplay muted playsinline></video>
                                </div>
                                @if (isset($photoVersions['placa']))
                                    <img id="photo-preview-placa" src="{{ $this->photoUrl('placa') }}" width="320" height="240" alt="Foto de placa">
                                @endif
                                <canvas wire:ignore hidden id="canvas2" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4" @if ($config->enable_fotovisitante != 1) hidden @endif>
                                <span><b>Visitante</b></span> <br>
                                <div @if (isset($photoVersions['visitante'])) hidden @endif>
                                    <video wire:ignore id="video3" width="320" height="240" autoplay muted playsinline></video>
                                </div>
                                @if (isset($photoVersions['visitante']))
                                    <img id="photo-preview-visitante" src="{{ $this->photoUrl('visitante') }}" width="320" height="240" alt="Foto de visitante">
                                @endif
                                <canvas wire:ignore hidden id="canvas3" width="320" height="240"></canvas>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 d-flex justify-content-center">
                                <button wire:click="requestPhotos" type="button" id="capture-btn"
                                    class="btn btn-sm btn-light-info font-weight-bolder text-uppercase mr-2 visit-input" @if (!$config->enable_fotolicencia && !($config->enable_fotoplaca ?? 1) && !$config->enable_fotovisitante) hidden @endif>Tomar
                                    foto</button>
                            </div>
                        </div>
                        </fieldset>
                        <div class="row">
                            <div class="col-md-12 my-lg-0 my-1 text-right">
                                <button wire:loading.attr="disabled" @if ($status === 'restoring' || $status === 'saved') disabled @endif type="submit" id="guardar"
                                    class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">
                                    <span wire:loading.remove wire:target="save">{{ $status === 'pending' ? 'Verificar / reintentar guardado' : 'Guardar' }}</span>
                                    <span wire:loading wire:target="save">Guardando…</span>
                                </button>
                                <button type="button" wire:click="cancel" wire:loading.attr="disabled"
                                    class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase"
                                    id="prueba">Cancelar</button>
                                @if ($confirmCancel)
                                    <div class="alert alert-warning mt-3">El guardado sigue pendiente de verificación.
                                        <button type="button" wire:click="cancel" class="btn btn-sm btn-light-danger">Salir del formulario</button>
                                        <button type="button" wire:click="keepEditing" class="btn btn-sm btn-light">Volver</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div wire:ignore.self class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
        data-backdrop="static" aria-labelledby="manual-dialog-title" id="modalimg" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header custom-bg text-white">
                    <span id="manual-dialog-title">Ingreso Manual</span>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <label for="nombre-manual">Nombre del visitante</label>
                            <input wire:model.defer="nombreManualInput" wire:keydown.enter.prevent="confirmManual" type="text"
                                class="form-control visit-input" id="nombre-manual" maxlength="30" autocomplete="off">
                            @error('nombreManualInput') <div class="text-danger mt-2">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="text-center my-12">
                        <button type="button" class="btn btn-secondary" wire:click="closeManual">Cancelar</button>
                        <button wire:click="confirmManual" wire:loading.attr="disabled" type="button" id="guardarManual"
                            class="btn btn-primary visit-input">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
