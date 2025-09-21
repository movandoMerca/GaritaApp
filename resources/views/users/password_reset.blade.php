@extends('layout.default')




@section('content')
    <div class="card card-custom gutter-b border custom-b">
        <div class="card-header flex-wrap border-0 pt-6 pb-0 custom-bg ">
            <div class="card-title">
                <h2 class="card-label text-white">Cambiar Contraseña</h2>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('savepw.user') }}" method="POST">
                @csrf
                <div class="row">

                    <div class="col-md-4">
                        <label class="badge badge-primary">Contraseña Actual</label>
                        <input class="form-control" type="password" name="actual" id="actual">
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>Contraseña Incorrecta</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-primary ">Nueva Contraseña</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                            name="password" required autocomplete="new-password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-primary">Confirmar Contraseña</label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                            required autocomplete="new-password">
                    </div>


                </div><br>

                <div class="form-group row mb-0">
                    <div class="col-md-12 text-center">
                        <input type="submit" class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase mr-2"
                            value="Guardar" />
                        <a href="javascript: history.go(-1)"
                            class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
                    </div>

                </div>
            </form>
        </div>
    @endsection
