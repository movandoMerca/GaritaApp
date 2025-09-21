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
        const camaraLicencia = '{!! Auth()->user()->camara_id_licencia !!}';
        const camaraPlaca = '{!! Auth()->user()->camara_id_placa !!}';
        const camaraVisitante = '{!! Auth()->user()->camara_id_visitante !!}';

        const constraints1 = {
            video: {
                deviceId: camaraLicencia
            }
        };
        const constraints2 = {
            video: {
                deviceId: camaraPlaca
            }
        };
        const constraints3 = {
            video: {
                deviceId: camaraVisitante
            }
        };

        const video1 = document.getElementById('video1');
        const video2 = document.getElementById('video2');
        const video3 = document.getElementById('video3');

        const canvas1 = document.getElementById('canvas1');
        const canvas2 = document.getElementById('canvas2');
        const canvas3 = document.getElementById('canvas3');

        const captureBtn = document.getElementById('capture-btn');

        var photo1, photo2, photo3;

        navigator.mediaDevices.getUserMedia(constraints1)
            .then((mediaStream) => {
                video1.srcObject = mediaStream;
            })
            .catch((err) => {
                console.log(`Error al acceder a la cámara web 1: ${err}`);
            });

        navigator.mediaDevices.getUserMedia(constraints2)
            .then((mediaStream) => {
                video2.srcObject = mediaStream;
            })
            .catch((err) => {
                console.log(`Error al acceder a la cámara web 2: ${err}`);
            });

        navigator.mediaDevices.getUserMedia(constraints3)
            .then((mediaStream) => {
                video3.srcObject = mediaStream;
            })
            .catch((err) => {
                console.log(`Error al acceder a la cámara web 3: ${err}`);
            });


        captureBtn.addEventListener('click', (e) => {

            e.preventDefault();

            // Tomar foto de la cámara 1
            const ctx1 = canvas1.getContext('2d');
            ctx1.drawImage(video1, 0, 0, canvas1.width, canvas1.height);
            photo1 = canvas1.toDataURL('image/png');

            // Tomar foto de la cámara 2
            const ctx2 = canvas2.getContext('2d');
            ctx2.drawImage(video2, 0, 0, canvas2.width, canvas2.height);
            photo2 = canvas2.toDataURL('image/png');

            // Tomar foto de la cámara 3
            const ctx3 = canvas3.getContext('2d');
            ctx3.drawImage(video3, 0, 0, canvas3.width, canvas3.height);
            photo3 = canvas3.toDataURL('image/png');

            $('#canvas1').removeAttr('hidden');
            $('#canvas2').removeAttr('hidden');
            $('#canvas3').removeAttr('hidden');

            $('#video1').attr('hidden', true);
            $('#video2').attr('hidden', true);
            $('#video3').attr('hidden', true);

            console.log(photo1);

            // Enviar las fotos a un servidor (ejemplo)
            // const xhr = new XMLHttpRequest();
            //     xhr.open('POST', '/guardar-fotos');
            //     xhr.setRequestHeader('Content-Type', 'application/json');
            //     xhr.send(JSON.stringify({
            //     photo1,
            //     photo2,
            //     photo3
            // }));
        });
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
