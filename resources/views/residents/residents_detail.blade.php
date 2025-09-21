@extends('layout.default')

@section('content')

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class="container">
            <!--begin::Profile 2-->
            <div class="d-flex flex-row">
                <!--begin::Aside-->
                <div class="flex-row-auto offcanvas-mobile w-300px w-xl-350px" id="kt_profile_aside">
                    <!--begin::Card-->
                    <div class="card card-custom">
                        <!--begin::Body-->
                        <div class="card-body pt-15 ">
                            <!--begin::User-->
                            
                            <span class="label label-light-success label-inline font-weight-bold label-lg">Activo</span><br>
                            <div class="text-center mb-10">

                               
                                <div class="symbol symbol-60 symbol-circle symbol-xl-90 "><br>
                                    <div class="symbol-label">
                                        <span class="svg-icon svg-icon-primary svg-icon-5x"><!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\legacy\metronic\theme\html\demo1\dist/../src/media/svg/icons\General\User.svg--><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <polygon points="0 0 24 0 24 24 0 24"/>
                                                <path d="M12,11 C9.790861,11 8,9.209139 8,7 C8,4.790861 9.790861,3 12,3 C14.209139,3 16,4.790861 16,7 C16,9.209139 14.209139,11 12,11 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                                                <path d="M3.00065168,20.1992055 C3.38825852,15.4265159 7.26191235,13 11.9833413,13 C16.7712164,13 20.7048837,15.2931929 20.9979143,20.2 C21.0095879,20.3954741 20.9979143,21 20.2466999,21 C16.541124,21 11.0347247,21 3.72750223,21 C3.47671215,21 2.97953825,20.45918 3.00065168,20.1992055 Z" fill="#000000" fill-rule="nonzero"/>
                                            </g>
                                        </svg><!--end::Svg Icon--></span>
                                    </div>
                                    <i class="symbol-badge symbol-badge-bottom bg-success"></i>
                                </div>
                                <h4 class="font-weight-bold my-2">{{ $resident->fullname(false) }} </h4>
                                <div class="text-muted mb-2">Cod: {{ $resident->Codigo}}</div>
                                @if ($resident->tipoResidente == 1)
                                <span class="label label-light-warning label-inline font-weight-bold label-lg">Residente</span>
                                @else
                                <span class="label label-light-info label-inline font-weight-bold label-lg">Inquilino</span>
                                @endif
                                
                               
                            </div>
                            <!--end::User-->

                            <!--begin::Nav-->
                            <a class=" custom-bg font-weight-bold py-3 px-6 mb-2 text-center text-white btn-block active">
                                Información de Contacto</a>

                            <a @if($config->enable_accesotel<>1) hidden @endif class=" bg-light-primary font-weight-bold py-3 px-6 mb-2 text-center btn-block "> <i class="fas fa-key text-primary"></i> {{ $resident->accesotel }}</a>
                            <a @if ($config->enable_tel<>1)hidden @endif class=" bg-light-primary font-weight-bold py-3 px-6 mb-2 text-center btn-block "> <i class="fas fa-phone-alt text-primary"></i>{{ $resident->Telefono }}</a>
                            <a class=" bg-light-primary font-weight-bold py-3 px-6 mb-2 text-center btn-block ">{{ $resident->Direccion }}</a>
                            
                                                        <!--end::Nav-->


                                                        

                    <div class="row py-8">
                        <div class="col-md-12 text-center">
                            <a href="javascript: history.go(-1)"  class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Regresar</a>
                        </div>
                       
                    </div><br>

                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Aside-->
                <!--begin::Content-->
                <div class="flex-row-fluid ml-lg-8">
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-md-12">
                            @include('pages.widgets._widget-resident', ['class' => 'card-stretch gutter-b','id'=>  $resident->id])
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Profile 2-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::Entry-->
@endsection
