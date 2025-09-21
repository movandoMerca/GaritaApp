
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap p-8 bgi-size-cover bgi-no-repeat rounded-top" style="background-image: url('{{ asset('media/misc/bg-1.jpg') }}')">
        <div class="d-flex align-items-center mr-2">
            {{-- Symbol --}}
            <div class="symbol bg-white-o-15 mr-3">
                <span class="symbol-label text-success font-weight-bold font-size-h4">{{  strtoupper(auth()->user()->name[0]) }}</span>
            </div>

            {{-- Text --}}
            <div class="text-white m-0 flex-grow-1 mr-3 font-size-h5">{{ ucfirst(auth()->user()->name) }}</div>
        </div>
      
    </div>


{{-- Nav --}}
<div class="navi navi-spacer-x-0 pt-5">
    {{-- Item --}}
    <a href="{{ route('detail.user',['id'=>auth()->id()]) }}" class="navi-item px-8">
        <div class="navi-link">
            <div class="navi-icon mr-2">
                <i class="
                fas fa-user-cog text-success"></i>
            </div>
            <div class="navi-text">
                <div class="font-weight-bold">
                    Mi perfil
                </div>
                <div class="text-muted">
                    Información de usuario
                </div>
            </div>
        </div>
    </a>
    <a href="{{ route('editPW.user',['id'=>auth()->id()]) }}" class="navi-item px-8">
        <div class="navi-link">
            <div class="navi-icon mr-2">
                <i class="
                fas fa-key text-success"></i>
            </div>
            <div class="navi-text">
                <div class="font-weight-bold">
                    Contraseña
                </div>
                <div class="text-muted">
                    Cambiar contraseña
                   
                </div>
            </div>
        </div>
    </a>

   

    {{-- Footer --}}
    <div class="navi-separator mt-3"></div>
    <div class="navi-footer  px-8 py-5">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" >
            @csrf
            <input type="submit" class="btn btn-light-danger font-weight-bold" value="Cerrar Sesión">
        </form>       

    </div>
</div>
