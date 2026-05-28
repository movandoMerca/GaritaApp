<!DOCTYPE html>
<html lang="es">

<head>
    <base>
    <meta charset="utf-8" />
    <title>Validacion</title>
    <meta name="description" content="Validacion" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Page Custom Styles(used by this page)-->
    <!--end::Page Custom Styles-->
    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="{{ asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles-->
    <!--begin::Layout Themes(used by all pages)-->
    <link href=" {{ asset('css/themes/layout/header/base/light.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/themes/layout/header/menu/light.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/themes/layout/brand/dark.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/themes/layout/aside/dark.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Layout Themes-->
    <link rel="shortcut icon" href="{{ asset('media/logos/favicon.ico') }}" />
</head>

<body>

    <div class="container py-15 px-15">


        <div class=" jumbotron card custom-b px-15" style="background-color: white;">
            <div class="card-header custom-bg" style="background-color: rgb(20,44,74)">
                <div class="row text-center">
                    <div class="col-md">
                        <label class="text-white ">
                            <h2>Comuniquese con soporte para obtener código de Acceso</h2>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('validar') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id">
                    <div class="row text-center px-15">
                        <div class="col-md">
                            <input type="hidden" name="id" value="{{ $token->id }}">
                            <label class="badge badge-success text-uppercase">Token</label>
                            <input id="token" type="text" class="form-control input-sm text-center" value="{{ $token->token }}" disabled>
                        </div>
                    </div><br><br>
                    <div class="row text-center px-15">
                        <div class="col-md">
                            <label class="badge badge-success text-uppercase">Código de Licencia</label>
                            <input id="code" name="pass" type="password" class="form-control input-sm text-center" required>
                        </div>
                    </div><br><br>
                    <div class="row text-center px-15">
                        <div class="col-md">
                            <button type="submit"
                                class="btn  btn-primary font-weight-bolder text-uppercase mr-2">Acceder</button>
                        </div>
                    </div><br><br><br>
                             </form>
                            @if ($config->path_config == null)           
                    <form action="{{ route('trial') }}" method="POST">
                        @csrf
                    <div class="row text-right px-15">
                        <div class="col-md">
                            <button type="submit"
                                class="btn btn-sm btn-warning font-weight-bolder text-uppercase mr-2">Iniciar Prueba</button>
                        </div>
                    </div>
                    @endif
                </form>
                
            </div>
        </div>
    </div>


    <!--begin::Global Config(global config for global JS scripts)-->
    <script>
        var KTAppSettings = {
            "breakpoints": {
                "sm": 576,
                "md": 768,
                "lg": 992,
                "xl": 1200,
                "xxl": 1400
            },
            "colors": {
                "theme": {
                    "base": {
                        "white": "#ffffff",
                        "primary": "#3699FF",
                        "secondary": "#E5EAEE",
                        "success": "#1BC5BD",
                        "info": "#8950FC",
                        "warning": "#FFA800",
                        "danger": "#F64E60",
                        "light": "#E4E6EF",
                        "dark": "#181C32"
                    },
                    "light": {
                        "white": "#ffffff",
                        "primary": "#E1F0FF",
                        "secondary": "#EBEDF3",
                        "success": "#C9F7F5",
                        "info": "#EEE5FF",
                        "warning": "#FFF4DE",
                        "danger": "#FFE2E5",
                        "light": "#F3F6F9",
                        "dark": "#D6D6E0"
                    },
                    "inverse": {
                        "white": "#ffffff",
                        "primary": "#ffffff",
                        "secondary": "#3F4254",
                        "success": "#ffffff",
                        "info": "#ffffff",
                        "warning": "#ffffff",
                        "danger": "#ffffff",
                        "light": "#464E5F",
                        "dark": "#ffffff"
                    }
                },
                "gray": {
                    "gray-100": "#F3F6F9",
                    "gray-200": "#EBEDF3",
                    "gray-300": "#E4E6EF",
                    "gray-400": "#D1D3E0",
                    "gray-500": "#B5B5C3",
                    "gray-600": "#7E8299",
                    "gray-700": "#5E6278",
                    "gray-800": "#3F4254",
                    "gray-900": "#181C32"
                }
            },
            "font-family": "Poppins"
        };
    </script>
    <!--end::Global Config-->
    <!--begin::Global Theme Bundle(used by all pages)-->
    <script src="{{ asset('plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('plugins/custom/prismjs/prismjs.bundle.js') }}"></script>
    <script src="{{ asset('js/scripts.bundle.js') }}"></script>
    <!--end::Global Theme Bundle-->
    <!--begin::Page Scripts(used by this page)-->
  


</body>
