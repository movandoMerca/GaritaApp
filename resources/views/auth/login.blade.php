<head>
    <base>
    <meta charset="utf-8" />
    <title>Login</title>
    <meta name="description" content="Login" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Page Custom Styles(used by this page)-->
    <link href="{{ asset('css/pages/login/classic/login-3.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Page Custom Styles-->
    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="{{ asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/custom/prismjs/prismjs.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
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

    <div class="d-flex flex-column flex-root">
        <!--begin::Login-->
        <div class="login login-3 login-signin-on d-flex flex-row-fluid" id="kt_login">
            <div class="d-flex flex-center bgi-size-cover bgi-no-repeat flex-row-fluid"
                style="background-image: url({{ asset('media/bg/prueba.jpg') }});">
                <div class="login-form text-center p-7 position-relative overflow-hidden">
                    <!--begin::Login Header-->
                    <div class="d-flex flex-center mb-15">
                        <a href="#">
                            <img src="{{ route('logo.config') }}" class="max-h-100px" alt="" />
                        </a>
                    </div>
                    <!--end::Login Header-->
                    <!--begin::Login Sign in form-->
                    <div class="login-signin">
                        <div class="mb-10">
                            <h3 class="custom-bg">Iniciar Sesión</h3>
                            <p class="opacity-60 font-weight-bold">Ingrese los datos de su cuenta:</p>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                         @csrf
                            <div class="form-group">

                                <input id="username" type="text"
                                    class="form-control h-auto text-white placeholder-white opacity-90 bg-dark-o-70 rounded-pill border-0 py-4 px-8 mb-5 @error('email') is-invalid @enderror"
                                    placeholder="Usuario" name="username" value="{{ old('username') }}" required
                                    autocomplete="username" autofocus>
                                @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif



                            <div class="form-group ">

                                <input id="password" type="password"
                                    class="form-control h-auto text-white placeholder-white opacity-90 bg-dark-o-70 rounded-pill border-0 py-4 px-8 mb-5 @error('password') is-invalid @enderror"
                                    placeholder="Contraseña" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                            </div>

                            <div class="form-group d-flex flex-wrap justify-content-between align-items-center px-8">
                                <div class="checkbox-inline custom-b">
                                    <label class="checkbox checkbox-outline checkbox-outline-2x checkbox-primary">
                                        <input type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>
                                        <span></span><b>Recuerdame</b></label>
                                </div>
                            </div>


                            <div class="form-group text-center mt-10">

                                <button type="submit"
                                    class="btn btn-pill btn-primary font-weight-bold opacity-90 px-15 py-3">
                                    Ingresar
                                </button>

                            </div>

                        </form>
                    </div>
                    <!--end::Login Sign in form-->

                </div>
            </div>
        </div>
        <!--end::Login-->
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
    <script src="{{ asset('js/pages/custom/login/login-general.js') }}"></script>


</body>
