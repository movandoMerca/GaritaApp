@extends('layout.default')


@section('styles')
    <!-- select2-bootstrap4-theme -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css">
    <!-- for live demo page -->
@endsection
@section('content')
    <form class="form">
        <div class="card">
            <div class="card card-custom  gutter-b">
                <div class="card-body">
                    <div class="container">
                        <div id="responset"></div>
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
                                            <input type="text" id="data" name="data" class="form-control"
                                                autofocus />
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
                                                    <select id="selectResidente" class="form-control p-7">
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
                                                    <input id="placa" type="text" class="form-control" />
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
                                                    <input id="cono" type="text" class="form-control input-sm" />
                                                </div>
                                            </div>
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
                            <div class="col-sm-4">
                                <span><b> Licencia </b></span> <br>
                                <video id="video1" width="320" height="240" autoplay></video>
                                <canvas hidden id="canvas1" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4">
                                <span><b><b>Placa</b></span> <br>
                                <video id="video2" width="320" height="240" autoplay></video>
                                <canvas hidden id="canvas2" width="320" height="240"></canvas>
                            </div>
                            <div class="col-sm-4">
                                <span><b>Visitante</b></span> <br>
                                <video id="video3" width="320" height="240" autoplay></video>
                                <canvas hidden id="canvas3" width="320" height="240"></canvas>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 d-flex justify-content-center">
                                <button id="capture-btn"
                                    class="btn btn-sm btn-light-info font-weight-bolder text-uppercase mr-2">Tomar
                                    foto</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 my-lg-0 my-1 text-right">
                                <a id="guardar"
                                    class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">Guardar</a>
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
                            <input type="text" class=" form-control" id="nombre-manual">
                        </div>
                    </div>

                    <div class="text-center my-12">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <a id="guardarManual" class="btn btn-primary"> Guardar </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        console.log(navigator.mediaDevices.enumerateDevices());
    </script>
    <script>
        $('#guardarManual').click(function (e) {
            e.preventDefault();
            nombreManual = $('#nombre-manual').val();
            console.log(nombreManual);
            $('#1nombre').text(nombreManual);
            $('#modalimg').modal('hide');
        });
    </script>

    <script>
        (async () => {
        // IDs guardados en el usuario (pueden ser deviceId antiguos o labels)
        const wanted = {
            licencia:  '{!! Auth()->user()->camara_id_licencia !!}',
            placa:     '{!! Auth()->user()->camara_id_placa !!}',
            visitante: '{!! Auth()->user()->camara_id_visitante !!}',
        };

        const els = {
            licencia:  { video: document.getElementById('video1'), canvas: document.getElementById('canvas1') },
            placa:     { video: document.getElementById('video2'), canvas: document.getElementById('canvas2') },
            visitante: { video: document.getElementById('video3'), canvas: document.getElementById('canvas3') },
        };

        // 1) Pide permiso una vez para poder leer labels y deviceId reales
        try { await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); }
        catch (e) {
            console.error('No se pudo obtener permiso de cámara:', e);
            Swal.fire('Permiso denegado', 'Autoriza el uso de la cámara para seleccionar los dispositivos correctos.', 'error');
            return;
        }

        // 2) Enumera cámaras y arma mapas por deviceId y por label
        const devices = (await navigator.mediaDevices.enumerateDevices())
                        .filter(d => d.kind === 'videoinput');

        const byId    = Object.fromEntries(devices.map(d => [d.deviceId, d]));
        const byLabel = Object.fromEntries(devices.map(d => [d.label, d]));

        // Utilidad: resuelve un “id guardado” a un deviceId válido
        const resolveDeviceId = (saved) => {
            if (!saved) return null;
            // a) Coincidencia exacta por deviceId
            if (byId[saved]) return saved;
            // b) Coincidencia por label exacta
            if (byLabel[saved]) return byLabel[saved].deviceId;
            // c) Coincidencia por label parcial (por si guardaste parte del nombre)
            const partial = devices.find(d => d.label && d.label.toLowerCase().includes(String(saved).toLowerCase()));
            return partial ? partial.deviceId : null;
        };

        const pickOrFallback = (role, usedSet) => {
            const resolved = resolveDeviceId(wanted[role]);
            if (resolved && !usedSet.has(resolved)) return resolved;
            // Fallback: escoge la primera cámara que no esté usada aún
            const firstFree = devices.find(d => !usedSet.has(d.deviceId));
            return firstFree ? firstFree.deviceId : null;
        };

        const used = new Set();
        const mapRoleToDeviceId = {
            licencia:  pickOrFallback('licencia', used),
            placa:     pickOrFallback('placa', used),
            visitante: pickOrFallback('visitante', used),
        };
        Object.values(mapRoleToDeviceId).forEach(id => id && used.add(id));

        // 3) Abre cada stream con constraint EXACTO
        const streams = {};

        const openStream = async (role) => {
            const deviceId = mapRoleToDeviceId[role];
            const el = els[role].video;
            if (!deviceId) {
            console.warn(`No hay cámara disponible para ${role}`);
            Swal.fire('Cámara no encontrada', `No se encontró una cámara para ${role}.`, 'warning');
            return;
            }
            // ¡Usa exact!
            const constraints = { video: { deviceId: { exact: deviceId } }, audio: false };
            try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            // Detén stream previo si reabrimos
            if (streams[role]) streams[role].getTracks().forEach(t => t.stop());
            streams[role] = stream;
            el.srcObject = stream;
            await el.play();
            console.log(`[${role}] usando:`, devices.find(d => d.deviceId === deviceId)?.label || deviceId);
            } catch (err) {
            console.error(`Error al abrir cámara de ${role}:`, err);
            Swal.fire('Error de cámara', `No se pudo abrir la cámara de ${role}.`, 'error');
            }
        };

        await Promise.all([openStream('licencia'), openStream('placa'), openStream('visitante')]);

        // 4) Captura de fotos (igual que tu lógica, pero más segura)
        const captureBtn = document.getElementById('capture-btn');
        window.photo1 = window.photo2 = window.photo3 = undefined;

        captureBtn.addEventListener('click', (e) => {
            e.preventDefault();

            const snap = (role, idx) => {
            const video  = els[role].video;
            const canvas = els[role].canvas;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.85); // JPEG para reducir peso
            document.getElementById(`video${idx}`).hidden  = true;
            document.getElementById(`canvas${idx}`).hidden = false;
            return dataUrl;
            };

            try {
            window.photo1 = snap('licencia', 1);
            window.photo2 = snap('placa', 2);
            window.photo3 = snap('visitante', 3);
            } catch (err) {
            console.error('Error al capturar fotos:', err);
            Swal.fire('Error', 'No se pudieron capturar las fotos.', 'error');
            }
        });

        // 5) Limpieza al salir de la página
        window.addEventListener('beforeunload', () => {
            Object.values(streams).forEach(s => s && s.getTracks().forEach(t => t.stop()));
        });

        // 6) Si quieres forzar HTTPS/local para estabilidad de deviceId:
        if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
            console.warn('Recomendado servir por HTTPS para estabilidad de deviceId.');
        }
        })();
        </script>




    <script>
        var data = "";
        var tokenizer = "";
        var nombreManual = "";


        var KTSelect2 = function() {
            $('#selectResidente').select2({
                theme: 'bootstrap4',

                placeholder: 'Seleccione numero de casa..',
                value: 'background-color: red'

            });

            return {
                init: function() {

                }
            };
        }();

        jQuery(document).ready(function() {
            KTSelect2.init();
        });

        function splitMulti(str, tokens) {
            var tempChar = tokens[0]; // We can use the first token as a temporary join character
            for (var i = 1; i < tokens.length; i++) {
                str = str.split(tokens[i]).join(tempChar);
            }
            str = str.split(tempChar);
            return str;
        }

        $(document).ready(function() {
            $("#data").keypress(function(e) {


                var code = (e.keyCode ? e.keyCode : e.which);
                if (code == 13) {
                    data = $("#data").val();

                    $("#data").val("");

                    var d = new Date();

                    var contador = 0;

                    while (contador <= 219) {
                        if (isNaN(data.charAt(contador))) {
                            break;
                        }
                        contador = contador + 1;
                    }

                    tokenizer = splitMulti(data, ['|', ']']);

                    console.log(tokenizer.length);
                    console.log(tokenizer);

                    if (tokenizer[0].length > 10) {
                        tokenizer[0] = tokenizer[0].substr(-8, 8);
                        tokenizer.splice(6, 1);
                        console.log(tokenizer);
                    }

                    if (tokenizer.length == 11) {
                        var iniciales = tokenizer[2].charAt(0) + tokenizer[4].charAt(0);
                        $("#iniciales").append(iniciales);
                        $("#1nombre").append(tokenizer[2]);
                        $("#2nombre").append(tokenizer[3]);
                        $("#1apellido").append(tokenizer[4]);
                        $("#2apellido").append(tokenizer[5]);
                        $("#icon").prop("hidden", false)
                        $("#fechanac").append(tokenizer[6]);
                        $("#cui").append(tokenizer[8]);
                        $("#telefonoEmergencia").append(tokenizer[9]);
                        $("#tipo").append(tokenizer[1]);
                        $("#ndocument").append(tokenizer[0]);
                        $("#fechavec").append(tokenizer[7]);
                        $("#horaentrada").append(d.toLocaleString());
                        $("#ocultar").prop("hidden", true);


                        // obtener datos del span
                        // var span_Text = document.getElementById("tipo").innerText;

                    } else {

                        Swal.fire({
                            title: 'Revisa que hayas escaneado el codigo correcto. ',
                            showClass: {
                                popup: 'animate__animated animate__backInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__backOutDown'
                            }
                        });

                        $("#modalimg").modal('show');

                    }


                }
            });
        });



        $('#selectResidente').on('change', function(e) {

            var id_residente = $('#selectResidente').val();


            $.ajax({
                format: 'json',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: '{{ route('detail_ajax.resident') }}',
                data: {
                    id: id_residente
                },
                success: function(data) {

                    var datos = JSON.parse(data);

                    $('#ftelefono').val(datos['Codigo']);
                    $('#direccion').val(datos['Direccion']);


                }

            });

        });



        $("#guardar").click(function() {

            $('#responset').empty()
                .removeAttr('class');

            console.log(tokenizer)
            var id_residente = $('#selectResidente').val();
            var placa = $('#placa').val();
            var cono = $('#cono').val();

            $.ajax({
                format: 'json',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                url: '{{ route('save.visits') }}',
                data: {
                    residente: id_residente,
                    placa: placa,
                    tokens: tokenizer,
                    cono: cono,
                    nombreManual:nombreManual,
                    photo1: photo1,
                    photo2: photo2,
                    photo3: photo3,
                },
                success: function(data) {

                    datas = JSON.parse(data);
                    console.log(datas);
                    $("#idv").val(datas['id']);

                    window.location.replace("/visit/detailvisit/" + datas['id']);



                },
                error: function(data) {
                    if (data.status === 422) {
                        var errors = $.parseJSON(data.responseText);
                        $.each(errors, function(key, value) {
                            // console.log(key+ " " +value);
                            $('#responset').addClass("alert alert-danger");

                            if ($.isPlainObject(value)) {
                                $.each(value, function(key, value) {
                                    $('#responset').show().append(value + "<br/>");

                                });
                            } else {
                                $('#responset').show().append(value +
                                    "<br/>"); //this is my div with messages
                            }
                        });
                    }
                }

            });

        });

        var avatar4 = new KTImageInput('kt_image_4');
        var avatar5 = new KTImageInput('kt_image_2');
    </script>
@endsection
