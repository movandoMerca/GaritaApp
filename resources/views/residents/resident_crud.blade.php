@extends('layout.default')

@section('content')


    <!--begin::Card-->
    <div class="card card-custom gutter-b border custom-b">
        <div class="card-header flex-wrap border-0 pt-6 pb-0 custom-bg ">
            <div class="card-title">
                <h2 class="card-label text-white">Lista de Residentes</h2>
                <input type="hidden" name="logo" id="logo" value="{{ $image }}">
            </div>
        </div>
        <div class="card-body">
            <!--begin: Datatable-->


            <div class="row">
                <div class="col-md-12">
                    <table class="table  table-bordered nowrap text-center" style="" id="residentstb">
                        <thead>
                            <tr>
                                <th style="width:5% font-size:small;">Codigo</th>
                                <th>Tipo</th>
                                <th>Nombres</th>                               
                                <th>Apellidos</th>                                
                                <th>Telefono</th>
                                <th>Acceso Tel.</th>
                                <th>Direccion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($residents as $resident)
                                <tr>
                                    <td>{{ $resident->Codigo }}</td>
                                    <td>@if ($resident->tipoResidente == 1)
                                        <span class="label label-light-warning label-inline font-weight-bold label-lg">Residente</span>
                                    @else
                                        <span class="label label-light-info label-inline font-weight-bold label-lg">Inquilino</span>
                                    @endif
                                        
                                    </td>
                                    <td>{{ $resident->Nombres.' '.$resident->Nombres2 }}</td>                                   
                                    <td>{{ $resident->Apellidos.' '.$resident->Apellidos2 }}</td>                                   
                                    <td>{{ $resident->Telefono }}</td>
                                    <td>{{ $resident->accesotel }}</td>
                                    <td>{{ $resident->Direccion }}</td>
                                    <td>
                                        <a href="{{ route('detail.resident',['id' => $resident->id]) }}"
                                        class="btn btn-sm btn-clean btn-icon" title="Detalle">
                                        <span class="svg-icon svg-icon-md svg-icon-primary"><!--begin::Svg Icon | path:C:\wamp64\www\keenthemes\legacy\metronic\theme\html\demo1\dist/../src/media/svg/icons\Communication\Address-card.svg--><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24"/>
                                                <path d="M6,2 L18,2 C19.6568542,2 21,3.34314575 21,5 L21,19 C21,20.6568542 19.6568542,22 18,22 L6,22 C4.34314575,22 3,20.6568542 3,19 L3,5 C3,3.34314575 4.34314575,2 6,2 Z M12,11 C13.1045695,11 14,10.1045695 14,9 C14,7.8954305 13.1045695,7 12,7 C10.8954305,7 10,7.8954305 10,9 C10,10.1045695 10.8954305,11 12,11 Z M7.00036205,16.4995035 C6.98863236,16.6619875 7.26484009,17 7.4041679,17 C11.463736,17 14.5228466,17 16.5815,17 C16.9988413,17 17.0053266,16.6221713 16.9988413,16.5 C16.8360465,13.4332455 14.6506758,12 11.9907452,12 C9.36772908,12 7.21569918,13.5165724 7.00036205,16.4995035 Z" fill="#000000"/>
                                            </g>
                                        </svg><!--end::Svg Icon--></span>
                                    </a>
                                        <a href="{{ route('edit.resident', ['id' => $resident->id]) }}"
                                            class="btn btn-sm btn-clean btn-icon" title="Editar">
                                            <span class="svg-icon svg-icon-md svg-icon-success">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px"
                                                    viewBox="0 0 24 24" version="1.1">
                                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                        <rect x="0" y="0" width="24" height="24" />
                                                        <path
                                                            d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z"
                                                            fill="#000000" fill-rule="nonzero"
                                                            transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) " />
                                                        <rect fill="#000000" opacity="0.3" x="5" y="20" width="15"
                                                            height="2" rx="1" />
                                                    </g>
                                                </svg>
                                            </span>
                                        </a>
                                        <a href="{{ route('delete.resident', ['id' => $resident->id]) }}"
                                            class="btn btn-sm btn-clean btn-icon" title="Eliminar">
                                            <span class="svg-icon svg-icon-md svg-icon-danger">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px"
                                                    viewBox="0 0 24 24" version="1.1">
                                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                        <rect x="0" y="0" width="24" height="24" />
                                                        <path
                                                            d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z"
                                                            fill="#000000" fill-rule="nonzero" />
                                                        <path
                                                            d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z"
                                                            fill="#000000" opacity="0.3" />
                                                    </g>
                                                </svg>
                                            </span>
                                        </a>
                                       
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div><br>

            <div class="row">
                <div class="col-md-12 text-center">
                    <a href="{{ route('create.resident') }}" class="btn btn-sm btn-light-primary font-weight-bolder text-uppercase ">
                        <span class="svg-icon svg-icon-md">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                                height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path
                                        d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                        fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                            <!--end::Svg Icon-->
                        </span>Nuevo Residente</a>
                    <!--end::Button-->
                    <a href="javascript: history.go(-1)"  class="btn btn-sm btn-light-danger font-weight-bolder text-uppercase">Cancelar</a>
                </div>
            </div>


            <!--end: Datatable-->
        </div>
    </div>
    <!--end::Card-->





@endsection
@section('scripts')
    <script src="{{ asset('js/residentlist_table.js') }}">
    </script>
@endsection
