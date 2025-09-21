@extends('layout.default')

@section('styles')
    <style type="text/css">
        #results {
            padding: 20px;
            border: 1px solid;
            background: #ccc;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.25/webcam.min.js"></script>
@endsection

@section('content')
    <div class="card card-custom gutter-b border custom-b ">
        <div class="card-header custom-bg">
            <div class="card-title">
                <h3 class="card-label">
                    <span class="text-white">Configurar Camaras</span>
                </h3>
            </div>
        </div>
        <div class="card-body">




                <div class="row">
                    <div class="col-md-6 text-center">
                        <div id="my_camera"></div>
                        <br>
                        <select id="videoSelect" class="form-control">
                            <option value="">Seleccione una cámara</option>
                        </select>
                        <br />
                        <input type="hidden" name="image" class="image-tag">
                        <input type="hidden" name="tipo" value="Placa">
                    </div>
                    <div class="col-md-6 text-center ">
                        <div class="form-group">
                            <span>Seleccionar el enfoque de la camara</span>
                            <select name="tipo" class="form-control" id="tipo">
                                <option value="placa">Placa</option>
                                <option value="visitante">Visitante</option>
                                <option value="licencia">Licencia</option>
                            </select>
                        </div>
                    </div>
                </div><br>
                <div class="col-md-12 text-center">

                    <button class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2" id="btn-guardar">Guardar</button>
                </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script language="JavaScript">

    var camaras = new Array();
    var contador = 0;
        navigator.mediaDevices.enumerateDevices()
            .then(function(devices) {
                devices.forEach(function(device) {
                    if (device.kind === 'videoinput') {
                        contador++;
                        var option = document.createElement('option');
                        option.value = device.deviceId;
                        option.text = 'Camera ' + contador;
                        videoSelect.appendChild(option);
                        camaras.push(device.deviceId);
                    }
                });
            })
            .catch(function(err) {
                console.error('Error al obtener la lista de dispositivos: ', err);
            });



        function take_snapshot() {
            Webcam.snap(function(data_uri) {
                $(".image-tag").val(data_uri);
                document.getElementById('results').innerHTML =
                    '<img style="max-width:100%; max-height:100%" src="' + data_uri + '"/>';
            });
        }
    </script>

    <script>
        function startStream(deviceId) {
            var constraints = {
                video: {
                    deviceId: deviceId
                }
            };

            Webcam.set( { //set the constraints and initialize camera device (0 or 1 for back and front - varies which is which depending on device)
                constraints: {
                deviceId: { exact: deviceId }
                            },
                width: 400,
                height: 300,
                image_format: 'jpeg',
                jpeg_quality: 90,
                sourceId: deviceId
                } );

            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(stream) {
                    Webcam.attach('#my_camera', '', stream);
                })
                .catch(function(err) {
                    console.error('Error al obtener acceso a la cámara: ', err);
                });
        }

        var videoSelect = document.getElementById('videoSelect');
        videoSelect.onchange = function() {
            var selectedDeviceId = videoSelect.value;
            startStream(selectedDeviceId);
        };


    </script>

    <script>
        $('#btn-guardar').click(function (e) {
            e.preventDefault();

            let camaraID = $('#videoSelect').val();
            let tipo = $('#tipo').val();

            $.ajax({
                type: "POST",
                url: "{{ route('saveConfig.user') }}",
                data: {
                    camaraID, tipo
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                success: function (response) {

                    $('#tipo option:selected').remove();
                    Swal.fire(
                        'Good job!',
                        'Continue con las siguientes Camaras',
                        'success'
                        )
                }
            });


        });
    </script>
@endsection
