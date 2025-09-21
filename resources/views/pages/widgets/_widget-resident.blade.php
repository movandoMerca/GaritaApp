{{-- List Widget 9 --}}


    <?php  $visits = App\Models\Visit::whereDate('fechaingreso','=',date('Y-m-d'))->where('residente_id',$id)->orderby('fechaingreso','desc')-> get(); ?>


<div class="card card-custom {{ @$class }}">
    {{-- Header --}}
    <div class="card-header align-items-center border-0 mt-4">
        <h3 class="card-title align-items-start flex-column">
            <span class="font-weight-bolder text-dark">Visitas del dia</span>
            <span class="text-muted mt-3 font-weight-bold font-size-sm">{{ $visits->count() }} Visitas</span>
        </h3>
       
    </div><br>

    <div class="row">
        <div class="text-center col-md-2"><label class="badge bg-secondary">Hora</label></div>
        <div class="text-left col-md-4"><label class="badge bg-secondary">Visitante</label></div>
        <div class="text-left col-md-6"><label class="badge bg-secondary">Residente</label></div>
    </div>
    {{-- Body --}}
    <div class="card-body pt-4">
        <div class="scroll scroll-pull" data-scroll="true" data-wheel-propagation="true" style="height: 400px">
        <div class="timeline timeline-6 mt-3">

            @foreach ($visits as $visit)
                
            
            <!--begin::Item-->
            <div class="timeline-item align-items-start">
                <!--begin::Label-->
                <div class="timeline-label font-weight-bolder text-dark-75 font-size-lg text-center">{{ date("H:i a",strtotime($visit->fechaingreso) ) }}</div>
                <!--end::Label-->
                <!--begin::Badge-->
                <div class="timeline-badge">
                    <i class="fa fa-genderless text-primary icon-xl"></i>
                </div>
                <!--end::Badge-->
                <!--begin::Text-->
                <div class="font-weight-mormal font-size-lg timeline-content text-muted pl-3"><label class="text-info">{{ $visit->Primer_Nombre }} {{ $visit->Primer_Apellido }}</label> visitó al residente <label class="text-primary">#{{ $visit->residente->Codigo }} - {{ $visit->residente->fullname(false) }}</label></div>
                <!--end::Text-->
            </div>
            <!--end::Item-->
            @endforeach
            
              

        </div>
         </div>
    </div>
</div>
