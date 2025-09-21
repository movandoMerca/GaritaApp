@extends('layout.default')

@section('content')


    <div class="card ">
        <div class="card-body pt-10 ">
            <div class="row justify-content-center">

                <div class="col-6">
                    <!--begin::User-->
                    <div class="d-flex align-items-center mb-7">
                        <!--begin::Pic-->
                        <div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
                            <div class="symbol symbol-circle symbol-lg-75 d-none">
                                <img src="assets/media/users/300_10.jpg" alt="image" />
                            </div>
                            <div class="symbol symbol-lg-75 symbol-circle symbol-primary">
                                <span class="symbol-label font-size-h1 font-weight-boldest">
                                   {{$user->name[0]}}
                                </span>
                            </div>
                        </div>
                        <!--end::Pic-->
                        <!--begin::Title-->
                        <div class="d-flex flex-column">
                            <a href="#"
                                class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">{{ $user->name }}</a>
                            <span class="text-muted font-weight-bold">{{ $user->username }}</span>
                        </div>
                        <!--end::Title-->
                    </div>
                    <!--end::User-->

                    <!--begin::Info-->
                    <div class="mb-10">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-dark-75 font-weight-bolder mr-2">Correo:</span>
                            <a href="#" class="text-muted text-hover-primary">{{ $user->email }}</a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center my-2">
                            <span class="text-dark-75 font-weight-bolder mr-2">Nivel de Acceso:</span>
                            <span class="text-muted font-weight-bold">
                                @if ($user->is_admin)
                                    <span
                                        class="label label-light-warning label-inline font-weight-bold label-lg">Administrador</span>
                                @else
                                    <span
                                        class="label label-light-info label-inline font-weight-bold label-lg">Usuario</span>
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-dark-75 font-weight-bolder mr-2">Estado:</span>
                            <span class="text-muted font-weight-bold">
                                @if ($user->status)
                                    <span
                                        class="label label-light-success label-inline font-weight-bold label-lg">Activo</span>
                                @else
                                    <span
                                        class="label label-light-danger label-inline font-weight-bold label-lg">Deshabilitado</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <!--end::Info-->
                    <div class="row">
                        <a
                            class="bg-light-primary font-weight-bold py-3 px-6 mb-2 text-center btn-block text-uppercase py-4">Registro</a>
                    </div><br>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-dark-75 font-weight-bolder mr-2">Fecha de creación:</span>
                        <a href="#" class="text-muted text-hover-primary">{{ $user->created_at }}</a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-dark-75 font-weight-bolder mr-2">Ultima modificación:</span>
                        <a href="#" class="text-muted text-hover-primary">{{ $user->updated_at }}</a>
                    </div><br><br>

                    <div class="row py-8">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('camaras.user') }}"  class="btn btn-sm btn-light-info font-weight-bolder text-uppercase"><i class="la la-camera"></i> Configurar Camaras</a>
                            <a href="javascript: history.go(-1)"  class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Regresar</a>
                        </div>

                    </div><br>


                </div>

            </div>




        </div>
    </div>

@endsection
