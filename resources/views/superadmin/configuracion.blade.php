@extends('layout.default')




@section('content')

    <div class="card border custom-b">
        <div class="card-header custom-bg">
            <span class="text-white text-center">
                <h2> Configuración</h2>
            </span>
        </div>
        <!--end::Header-->
        <!--begin::Body-->
        <div class="card-body pt-2">
            <form action="{{ route('save.config') }}" method="POST" enctype="multipart/form-data">
                @csrf
            <div class="row">
                <div class="col-md-6">
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center mb-10">
                                <div class="form-group">
                                    <label><a href="#"
                                            class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Brand</a></label>
                                    <div></div>
                                    <div class="custom-file text-center">
                                        <input type="file" class="custom-file-input" id="path_brand" name="path_brand" accept="image/png"/>
                                        <label class="custom-file-label" for="brand">.png*</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center mb-10">
                                <div class="form-group">
                                    <label><a href="#"
                                            class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Logo</a></label>
                                    <div></div>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="path_logo" name="path_logo" accept="image/png" />
                                        <label class="custom-file-label" for="logo">.png*</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                   
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-12"><br>
                            <div class="d-flex flex-wrap align-items-center mb-10">
                                <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4 ena">
                                    <span class="switch switch-outline switch-icon switch-primary">
                                        <label>
                                            <input type="checkbox" name="enable_fotolicencia" id="enable_fotolicencia" @if ($config->enable_fotolicencia == 1)
                                                checked
                                            @endif>
                                            <span></span>
                                        </label>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                    <a class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Foto
                                        de
                                        Licencia</a>
                                    <span class="text-primary font-weight-bold font-size-sm my-1">Sección: Visitas</span>
                                    <span class="text-muted font-weight-bold font-size-sm">Habilita sección para toma de
                                        foto de de
                                        la licencia
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center mb-10">
                                <!--begin::Symbol-->
                                <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4">
                                    <span class="switch switch-outline switch-icon switch-primary">
                                        <label>
                                            <input type="checkbox" name="enable_fotovisitante" id="enable_fotovisitante" @if ($config->enable_fotovisitante == 1)
                                                checked
                                            @endif>
                                            <span></span>
                                        </label>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                    <a class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Foto
                                        del
                                        Visitante</a>
                                    <span class="text-primary font-weight-bold font-size-sm my-1">Sección: Visitas</span>
                                    <span class="text-muted font-weight-bold font-size-sm">Habilita sección para toma de
                                        foto del
                                        visitante</span>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center mb-10">
                                <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4">
                                    <span class="switch switch-outline switch-icon switch-primary">
                                        <label>
                                            <input type="checkbox" name="enable_accesotel" id="enable_accesotel" @if ($config->enable_accesotel == 1)
                                            checked
                                        @endif>
                                            <span></span>
                                        </label>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                    <a href="#" class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Cod.
                                        Acceso
                                        Telefónico</a>
                                    <span class="text-primary font-weight-bold font-size-sm my-1">Sección:
                                        Resientes,Visitas</span>
                                    <span class="text-muted font-weight-bold font-size-sm">Habilita campo para código de
                                        acceso
                                        telefónico del resiente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center mb-10">

                                <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4">
                                    <span class="switch switch-outline switch-icon switch-primary">
                                        <label>
                                            <input type="checkbox" name="enable_tel" id="enable_tel" @if ($config->enable_tel == 1)
                                            checked
                                        @endif> 
                                            <span></span>
                                        </label>
                                    </span>
                                </div>
                                <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                    <a href="#"
                                        class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Teléfono</a>
                                    <span class="text-primary font-weight-bold font-size-sm my-1">Sección: Residentes</span>
                                    <span class="text-muted font-weight-bold font-size-sm">Habilita sección para numero de
                                        teléfono del resiente</span>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                     <div class="col-md-12">
                         <div class="d-flex flex-wrap align-items-center mb-10">

                             <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4">
                                 <span class="switch switch-outline switch-icon switch-primary">
                                     <label>
                                         <input type="checkbox" name="enable_egreso" id="enable_egreso"  @if ($config->enable_egreso == 1)
                                         checked
                                     @endif>
                                         <span></span>
                                     </label>
                                 </span>
                             </div>
                             <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                 <a href="#"
                                     class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">Egreso</a>
                                 <span class="text-primary font-weight-bold font-size-sm my-1">Sección: Visitas</span>
                                 <span class="text-muted font-weight-bold font-size-sm">Habilita campo para egreso de visitantes</span>

                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex flex-wrap align-items-center mb-10">

                            <div class="symbol symbol-60 symbol-2by3 flex-shrink-0 mr-4">
                                <span class="switch switch-outline switch-icon switch-primary">
                                    <label>
                                        <input type="checkbox" name="enable_webcam" id="enable_webcam"  @if ($config->enable_webcam == 1)
                                        checked
                                    @endif>
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                            <div class="d-flex flex-column flex-grow-1 my-lg-0 my-2 pr-3">
                                <a href="#"
                                    class="text-dark-75 font-weight-bolder text-hover-primary font-size-lg">WebCam</a>
                                <span class="text-primary font-weight-bold font-size-sm my-1">Sección: Visitas</span>
                                <span class="text-muted font-weight-bold font-size-sm">Habilita tomar foto dentro de la aplicacion</span>

                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
<br>

            <div class="form-group row mb-0">
               <div class="col-md-12 text-center">
                   <input type="submit" class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2" value="Guardar" />
                   <a href="javascript: history.go(-1)" class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
               </div>

            </form>
           </div>

        </div>
        <!--end::Body-->
    </div>



@endsection
