{{-- Mixed Widget 1 --}}

<?php  
    $date = strtotime(date("Y-m-d"));

    $first = strtotime('last Sunday');
    $last = strtotime('next Saturday');  
    $from = date('Y-m-d', $first);
    $to = date('Y-m-d', $last);

    $visits = App\Models\Visit::whereBetween('fechaingreso',[$from,$to])->get();
   

    ?>



<div class="card card-custom bg-gray-100 {{ @$class }}">
    {{-- Header --}}
    <div class="card-header border-0 bg-primary py-5">
        <h3 class="card-title font-weight-bolder text-white">Control de Visitas</h3>
      
    </div>
    {{-- Body --}}
    <div class="card-body p-0 position-relative overflow-hidden">
        {{-- Chart --}}
        <div id="kt_mixed_widget_1_chart" class="card-rounded-bottom bg-primary " style="height: 80px"></div>

        {{-- Stats --}}
        <div class="card-spacer mt-n25">
            {{-- Row --}}
            <div class="row">
                <div class="col-md-1 px-3 py-3 rounded-xl" @if (auth()->user()->is_admin==1) hidden @endif></div>
                <div class="col bg-light-success rounded-xl text-center" style="min-height: 130px;">
                   <h1 class="text-success "> <i class="fas fa-id-card d-block my-2 icon-5x text-success"></i> </h1>
                    <a class="text-dark font-weight-bold font-size-h6">
                       {{ $visits->count() }} Visitas en la semana
                    </a>
                </div>
                
                <div class="col-md-1 px-3 py-3 rounded-xl"></div>
                <div class="col bg-light-warning px-3 py-3 rounded-xl text-center" @if (auth()->user()->is_admin==0) hidden @endif>
                     <i class="fas fa-user-shield d-block my-2 text-warning icon-5x"></i> 
                    <a href="{{ route('index.user') }}" class="text-dark font-weight-bold font-size-h6 mt-2">
                        Usuarios Activos
                    </a>
                </div>
            </div><br>
            {{-- Row --}}
            <div class="row">
                <div class="col bg-light-danger px-3 py-3 rounded-xl text-center">
                    <i class="fas fa-house-user d-block my-2 text-danger icon-5x"></i> 
                    <a href="{{ route('index.resident') }}" class="text-dark font-weight-bold font-size-h6 mt-2">
                        Residentes
                    </a>
                </div>
                <div class="col-md-1 px-3 py-3 rounded-xl"></div>
                <div class="col bg-light-info px-3 py-3 rounded-xl text-center">
                    <i class="fas fa-user-clock d-block my-2 text-info icon-5x"></i> 
                    <a href="{{ route('detail.visits') }}"class="text-dark font-weight-bold font-size-h6 mt-2">
                        Visitas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


</script>