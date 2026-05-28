@extends('layout.default')

@section('content')


<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border custom-b">
                <div class="card-header custom-bg">
                    <span class="text-white text-center">
                        <h2> Editar Residente</h2>
                    </span>
                </div>


                <!--begin::Body-->
                <div class="card-body">
                    <form class="form" method="POST" action="{{ route('saveEdit.resident') }}" id="residentsform">

                        @csrf

                        <input type="hidden" name="id" id="id" value="{{ $resident->id }}">
                        <div class="row">
                            <label class="col-xl-3"></label>
                            <div class="col-lg-9 col-xl-6">
                                <h5 class="font-weight-bold mb-6">Datos del residente</h5>
                            </div>
                        </div>
                        <div class="form-group row">
                                <label class="col-xl-3 col-lg-3 col-form-label">Codigo</label>
                                <div class="col-lg-9 col-xl-9">
                                    <input
                                        class="form-control form-control-sm form-control-solid @error('Codigo') is-invalid @enderror"
                                        value="{{ $resident->Codigo }}" type="text" name="Codigo" id="Codigo" />
                                    @error('Codigo')
                                        <div class="alert text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Primer Nombre</label>
                            <div class="col-lg-9 col-xl-9">
                                <input
                                    class="form-control form-control-sm form-control-solid @error('Nombres') is-invalid @enderror"
                                    value="{{ $resident->Nombres }}" type="text" name="Nombres" id="Nombres"  />
                                @error('Nombres')
                                    <div class="alert text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Segundo Nombre</label>
                            <div class="col-lg-9 col-xl-9">
                                <input
                                    class="form-control form-control-sm form-control-solid @error('Nombres2') is-invalid @enderror"
                                    value="{{ $resident->Nombres2  }}" type="text" name="Nombres2" id="Nombres2" />
                                @error('Nombres2')
                                    <div class="alert text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Apellido</label>
                            <div class="col-lg-9 col-xl-9">
                                <input
                                    class="form-control form-control-sm form-control-solid @error('Apellidos') is-invalid @enderror"
                                    value='{{ $resident->Apellidos }}' type="text" name="Apellidos" id="Apellidos" />
                                @error('Apellidos')
                                    <div class="alert text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Segundo Apellido</label>
                            <div class="col-lg-9 col-xl-9">
                                <input
                                    class="form-control form-control-sm form-control-solid @error('Apellidos2') is-invalid @enderror"
                                    value='{{ $resident->Apellidos2 }}' type="text" name="Apellidos2" id="Apellidos2" />
                                @error('Apellidos2')
                                    <div class="alert text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-xl-3"></label>
                            <div class="col-lg-9 col-xl-9">
                                <h5 class="font-weight-bold mt-10 mb-6">Información de contacto</h5>
                            </div>
                        </div>
                        <div class="form-group row" @if ($config->enable_tel<>1)hidden @endif>
                            <label class="col-xl-3 col-lg-3 col-form-label">Teléfono</label>
                            <div class="col-lg-9 col-xl-9">
                                <div class="input-group input-group-sm input-group-solid bg-primary">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text ">
                                            <i class="la la-phone text-white"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="bg-light form-control form-control-sm @error('Telefono') is-invalid @enderror" value="{{ $resident->Telefono }}" name="Telefono" id="Telefono" />
                                </div>
                                @error('Telefono')
                                    <div class="alert text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row" @if ($config->enable_accesotel<>1)hidden @endif>
                             <label class="col-xl-3 col-lg-3 col-form-label">Acceso Teléfonico</label>
                            <div class="col-lg-9 col-xl-9">
                                <div class="input-group input-group-sm input-group-solid bg-primary @error('accesotel') is-invalid @enderror">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text ">
                                            <i class="la la-phone text-white"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="bg-light form-control form-control-sm" name="accesotel" id="accesotel" value="{{ $resident->accesotel }}" />
                                    @error('accesotel')
                                    <div class="alert text-danger">{{ $message }}</div>
                                     @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Dirección</label>
                            <div class="col-lg-9 col-xl-9">
                                <div class="input-group input-group-sm input-group-solid bg-primary">
                                    <div class="input-group-prepend ">
                                        <span class="input-group-text ">
                                            <i class="fas fa-house-user text-white"></i>
                                        </span>
                                    </div>
                                    <input type="text"
                                        class=" bg-light form-control form-control-sm  @error('Direccion') is-invalid @enderror"
                                        value="{{ $resident->Direccion }}" name="Direccion" id="Direccion" />
                                </div>
                                @error('Direccion')
                                    <div class="alert text-danger text-xs">{{ __($message) }}</div>
                                @enderror
                            </div>

                        </div>


                        <div class="form-group row">
                            <label for="admin"
                                class="col-xl-3 col-lg-3 col-form-label">Residente Activo</label>
                            <div class="col-md-6">
                                <span class="switch switch-outline switch-icon switch-primary">
                                    <label>
                                        <input type="checkbox"  name="estado" @if($resident->estado == 1)  checked  @endif />
                                        <span></span>
                                    </label>
                                </span>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-xl-3 col-lg-3 col-form-label">Tipo</label>
                            <div class="col-lg-9 col-xl-9">
                                <input type="hidden" name="tipo" id="tipo" value="{{ $resident->tipoResidente == 1 ? '1' : '0' }}">
                                <div class="btn-group btn-group-sm resident-type-toggle" role="group" aria-label="Tipo de residente">
                                    <button type="button" class="btn {{ $resident->tipoResidente == 1 ? 'btn-primary active' : 'btn-light-secondary' }}" data-resident-type="1">Residente</button>
                                    <button type="button" class="btn {{ $resident->tipoResidente == 1 ? 'btn-light-secondary' : 'btn-primary active' }}" data-resident-type="0">Inquilino</button>
                                </div>
                            </div>
                        </div>
                      

                        <div class="form-group row mb-0 text-right">
                            <div class="col-md-12 ">
                                <button type="submit" class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2"> Guardar</button>
                                <a href="javascript: history.go(-1)" class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
                            </div>
                        </div>

                        


                        <!--end::Form-->
                    </form>
                </div>
                <!--end::Body-->


            </div>

        </div>
    </div>
</div>

@endsection


@section('scripts')
    <script>
        jQuery(document).ready(function() {
            $('[data-resident-type]').on('click', function() {
                var selectedType = $(this).data('resident-type').toString();

                $('#tipo').val(selectedType);
                $('[data-resident-type]').removeClass('btn-primary active').addClass('btn-light-secondary');
                $(this).removeClass('btn-light-secondary').addClass('btn-primary active');
            });
        });
    </script>
@endsection
