@extends('layout.default')


@section('content')




    <div class="card card-custom gutter-b border custom-b ">
        <div class="card-header custom-bg">
            <div class="card-title">
                <h3 class="card-label">
                    <span class="text-white">Carga csv</span>
                </h3>
            </div>
        </div>
        <div class="card-body">

<div class="row">
<div class="col-md">
    <input class="form-control" type="file" id="fileUpload" />
</div>
<div class="col-md">
    <input class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2 form-control" type="button" id="upload" value="Cargar datos" />
</div>
</div>


           
            
           
            <div id="dvCSV">
                <meta name="csrf-token" content="{{ csrf_token() }}">
            </div>

        </div>
    </div>








@endsection

@section('scripts')

    <script>
        $(function() {
            $("#upload").bind("click", function() {
                var regex = /^([a-zA-Z0-9\s_\\.\-:])+(.csv|.txt)$/;
                if (regex.test($("#fileUpload").val().toLowerCase())) {
                    if (typeof(FileReader) != "undefined") {
                        var reader = new FileReader();
                        console.log(reader);

                        reader.onload = function(e) {
                            var rows = e.target.result.split("\n");
                            console.log(rows);
                            $.ajax({
                                format: 'json',
                                type: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                url: '{{ route('savecsv.resident') }}',
                                data: {
                                    rows: rows,
                                    '_token': $('input[name=_token]').val(),
                                },
                                success: function(data) {

                                    var datos = JSON.parse(data);
                                    Swal.fire({
                                        title: 'Ingresados Correctamente. ',
                                        showClass: {
                                            popup: 'animate__animated animate__backInDown'
                                        },
                                        hideClass: {
                                            popup: 'animate__animated animate__backOutDown'
                                        }
                                    });

                                    window.location.replace('/resident/index');


                                }

                            }).fail(function() {

                                Swal.fire({
                                    title: 'Error al ingresar los datos, revise su csv. Verifique que el Codigo de los residentes no esten duplicados',
                                    showClass: {
                                        popup: 'animate__animated animate__backInDown'
                                    },
                                    hideClass: {
                                        popup: 'animate__animated animate__backOutDown'
                                    }
                                });


                            });

                        }
                        reader.readAsText($("#fileUpload")[0].files[0]);
                    } else {
                        Swal.fire({
                            title: 'Este navegador no soporta html5',
                            showClass: {
                                popup: 'animate__animated animate__backInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__backOutDown'
                            }
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Por favor. Ingrese un archivo .csv valido',
                        showClass: {
                            popup: 'animate__animated animate__backInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__backOutDown'
                        }
                    });
                }
            });
        });
    </script>


@endsection
