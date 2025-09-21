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
                <h3 class="card-label text-center">
                    <span class="text-white ">Tomar Foto de la Licencia</span>
                </h3>
            </div>
        </div>
        <div class="card-body text-center">
            <form method="POST" action="{{ route('saveimgcamara.visits') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 text-center">
                        <div id="my_camera">
                        </div>
                        <br>
                        <select id="videoSelect" class="form-control">
                            <option value="">Seleccione una cámara</option>
                        </select>
                        <br />
                        <input type=button value="Tomar Foto" class="btn btn-success"" onClick="take_snapshot()">
                        <input type="hidden" name="image" class="image-tag">
                        <input type="hidden" name="tipo" value="Licencia" >
                        <input type="hidden" name="id" value="{{ $visit->id }}">
                    </div>
                    <div class="col-md-6 ">
                        <span class="badge badge-pill badge-primary">Captura</span>
                        <div id="results" style="background-color:white; border-color:white; width:500;" > 
                            <i class="fas fa-id-card text-Secondary icon-10x"></i>
                        </div>
                    </div>
                </div><br>

             

                <div class="col-md-12 text-center">
                    <button class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2">Guardar</button>
                </div>
            </form>
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

        Webcam.set({ //set the constraints and initialize camera device (0 or 1 for back and front - varies which is which depending on device)
            constraints: {
                deviceId: {
                    exact: deviceId
                }
            },
            width: 400,
            height: 300,
            image_format: 'jpeg',
            jpeg_quality: 90,
            sourceId: deviceId
        });

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


@endsection
