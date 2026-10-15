@extends('layout.default')

@section('styles')
    <link href="{{ asset('vendor/cropperjs/cropper.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        #dpi-camera-stage,
        #dpi-crop-stage { position: relative; overflow: hidden; border-radius: 8px; background: #182536; }
        #dpi-video { display: block; width: 100%; max-height: 58vh; object-fit: contain; }
        #dpi-frame { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 3px dashed #fff; border-radius: 12px; pointer-events: none; box-shadow: 0 0 0 100vmax #0005; }
        #dpi-crop-image { display: block; max-width: 100%; max-height: 58vh; }
        #dpi-preview { display: block; width: 100%; max-width: 760px; max-height: 58vh; object-fit: contain; border-radius: 8px; background: #182536; }
        #pedestrian-form .select2-selection--single { height: 44px; border-color: #e4e6ef; }
        #pedestrian-form .select2-selection__rendered { line-height: 42px; padding-left: 12px; font-size: 16px; }
        #pedestrian-form .select2-selection__arrow { height: 42px; }
        #pedestrian-form input.form-control,
        #pedestrian-form select.form-control { min-height: 44px; font-size: 16px; }
        #pedestrian-form .btn { min-height: 44px; padding-left: 16px; padding-right: 16px; }
        #dpi-capture-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        #dpi-crop-controls { display: flex; flex-wrap: wrap; gap: 8px; }
        #dpi-review-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        #pedestrian-form .dpi-needs-review { border-color: #b77500; }
        #pedestrian-form .dpi-field-status { display: block; min-height: 18px; margin-top: 4px; color: #795000; }
        #dpi-camera-quality { font-size: 13px; }
        .dpi-step { margin-bottom: 24px; }
        @media (max-width: 575.98px) {
            #pedestrian-form .card-body { padding: 16px; }
            #pedestrian-form .form-group { margin-bottom: 16px; }
            #dpi-capture-actions .btn,
            #dpi-crop-controls .btn,
            #dpi-use-crop,
            #dpi-retake,
            #dpi-retry,
            #dpi-edit,
            #dpi-back-review,
            #dpi-save,
            #pedestrian-form a.btn { width: 100%; }
            #dpi-crop-controls .btn { flex: 1 1 calc(50% - 8px); width: calc(50% - 8px); }
            #dpi-crop-controls #dpi-cancel-crop { flex-basis: 100%; }
            #dpi-video, #dpi-crop-image, #dpi-preview { max-height: 48vh; }
            #dpi-frame { border-width: 2px; }
        }
    </style>
@endsection

@section('content')
    <form id="pedestrian-form" action="{{ route('save.pedestrian.visits') }}" data-user="{{ auth()->id() }}"
        data-ocr-base="{{ asset('vendor/tesseract') }}" enctype="multipart/form-data">
        @csrf
        <div class="card card-custom gutter-b">
            <div class="card-header"><h3 class="card-title">Registro de visita peatonal</h3></div>
            <div class="card-body">
                <div id="dpi-errors" class="alert alert-danger" role="alert" hidden></div>
                <div class="row">
                    <div class="col-xl-6 mb-6">
                        <section class="dpi-step" aria-labelledby="dpi-capture-title">
                            <h4 id="dpi-capture-title">1. Capturar el DPI</h4>
                            <p>Use buena luz y encuadre el frente del DPI completo, sin reflejos.</p>
                            <div id="dpi-capture-actions" class="mb-3">
                                <button type="button" id="dpi-start" class="btn btn-primary">Abrir cámara</button>
                                <button type="button" id="dpi-native-start" class="btn btn-light-primary">Tomar foto del DPI</button>
                                <select id="dpi-camera" class="form-control" aria-label="Seleccionar cámara" hidden></select>
                            </div>
                            <button type="button" id="dpi-back-review" class="btn btn-light mb-3" hidden>Volver a la foto actual</button>
                            <input type="file" id="dpi-native-file" accept="image/*" capture="environment" hidden>
                            <div id="dpi-camera-stage" hidden>
                                <video id="dpi-video" autoplay muted playsinline></video>
                                <div id="dpi-frame" hidden></div>
                            </div>
                            <button type="button" id="dpi-capture" class="btn btn-primary my-3" disabled hidden>Capturar y leer</button>
                            <p id="dpi-camera-quality" class="mt-2" role="status" aria-live="polite" hidden></p>
                            <div id="dpi-crop-panel" hidden>
                                <p class="mb-2">Ajuste el encuadre para incluir todo el DPI.</p>
                                <div id="dpi-crop-stage"><img id="dpi-crop-image" alt="Ajustar encuadre del DPI"></div>
                                <div id="dpi-crop-controls" class="my-3" role="group" aria-label="Ajustes de la foto">
                                    <button type="button" id="dpi-straighten-left" class="btn btn-light-primary" aria-label="Enderezar un grado a la izquierda">Inclinar −1°</button>
                                    <button type="button" id="dpi-straighten-right" class="btn btn-light-primary" aria-label="Enderezar un grado a la derecha">Inclinar +1°</button>
                                    <button type="button" id="dpi-rotate-left" class="btn btn-light-primary" aria-label="Girar a la izquierda">Girar ↶</button>
                                    <button type="button" id="dpi-rotate-right" class="btn btn-light-primary" aria-label="Girar a la derecha">Girar ↷</button>
                                    <button type="button" id="dpi-zoom-out" class="btn btn-light-primary" aria-label="Alejar">−</button>
                                    <button type="button" id="dpi-zoom-in" class="btn btn-light-primary" aria-label="Acercar">+</button>
                                    <button type="button" id="dpi-cancel-crop" class="btn btn-light-danger">Cancelar recorte</button>
                                </div>
                                <button type="button" id="dpi-use-crop" class="btn btn-primary btn-block">Usar recorte y leer</button>
                            </div>
                            <div id="dpi-review" hidden>
                                <img id="dpi-preview" alt="Captura del frente del DPI">
                                <div id="dpi-review-actions">
                                    <button type="button" id="dpi-retry" class="btn btn-primary">Reintentar lectura</button>
                                    <button type="button" id="dpi-edit" class="btn btn-light-primary">Ajustar encuadre</button>
                                    <button type="button" id="dpi-retake" class="btn btn-light-primary">Tomar otra foto</button>
                                </div>
                            </div>
                            <p id="dpi-status" class="mt-3" role="status" aria-live="polite">Elija cómo tomar la foto del DPI.</p>
                            <progress id="dpi-progress" max="1" value="0" class="w-100" hidden aria-label="Progreso de lectura"></progress>
                        </section>
                    </div>
                    <div class="col-xl-6">
                        <section class="dpi-step" aria-labelledby="dpi-visitor-title">
                            <h4 id="dpi-visitor-title">2. Revisar los datos</h4>
                            <p>Corrija lo que sea necesario. La foto y los datos quedan en este dispositivo hasta que guarde.</p>
                            <div class="row">
                                @foreach (['Primer_Nombre' => ['Primer nombre', 30], 'Segundo_Nombre' => ['Otros nombres', 70], 'Primer_Apellido' => ['Primer apellido', 45], 'Segundo_Apellido' => ['Otros apellidos', 70]] as $field => $definition)
                                    <div class="form-group col-md-6">
                                        <label for="{{ $field }}">{{ $definition[0] }}</label>
                                        <input type="text" id="{{ $field }}" name="{{ $field }}" maxlength="{{ $definition[1] }}" class="form-control" aria-describedby="{{ $field }}-ocr" @if(in_array($field, ['Primer_Nombre', 'Primer_Apellido'])) required @endif>
                                        <small id="{{ $field }}-ocr" class="dpi-field-status"></small>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-group">
                                <label for="cui">DPI / CUI (13 dígitos)</label>
                                <input type="text" id="cui" name="cui" class="form-control" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" aria-describedby="cui-ocr" required>
                                <small id="cui-ocr" class="dpi-field-status"></small>
                            </div>
                            <div class="form-group">
                                <label for="Fecha_nac">Fecha de nacimiento</label>
                                <input type="date" id="Fecha_nac" name="Fecha_nac" class="form-control" max="{{ date('Y-m-d') }}" aria-describedby="Fecha_nac-ocr" required>
                                <small id="Fecha_nac-ocr" class="dpi-field-status"></small>
                            </div>
                        </section>
                        <section class="dpi-step" aria-labelledby="dpi-resident-title">
                            <h4 id="dpi-resident-title">3. Seleccionar residente</h4>
                            <div class="form-group">
                                <label for="pedestrian-resident">¿A quién visita?</label>
                                <select id="pedestrian-resident" name="residente" class="form-control" required>
                                    <option value="">Seleccione por nombre o código de casa</option>
                                    @foreach ($residents as $resident)
                                        <option value="{{ $resident->id }}" data-address="{{ $resident->Direccion }}">{{ $resident->fullname(true) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group"><label for="pedestrian-address">Dirección</label><input id="pedestrian-address" class="form-control" readonly></div>
                        </section>
                        <section aria-labelledby="dpi-save-title">
                            <h4 id="dpi-save-title" class="sr-only">4. Guardar visita</h4>
                            <button type="submit" id="dpi-save" class="btn btn-primary" disabled>Guardar visita</button>
                            <a href="{{ route('home') }}" class="btn btn-light-danger">Cancelar</a>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/tesseract/tesseract.min.js') }}" defer></script>
    <script src="{{ asset('vendor/cropperjs/cropper.min.js') }}" defer></script>
    <script src="{{ asset('js/dpi-parser.js') }}" defer></script>
    <script src="{{ asset('js/dpi-ocr.js') }}" defer></script>
    <script src="{{ asset('js/pedestrian-visit.js') }}" defer></script>
@endsection
