{{-- Header Mobile --}}


<div id="kt_header_mobile" class="header-mobile {{ Metronic::printClasses('header-mobile', false) }}" {{ Metronic::printAttrs('header-mobile') }}>
    <div class="mobile-logo">
        <a href="{{ url('/') }}">

            @php
                $kt_logo_image = 'mardysa.png';              
            @endphp

            

            <img alt="{{ config('app.name') }}" style="max-width: 100px;" src="{{ route('brand.config') }}"/>
        </a>
    </div>
    <div class="d-flex align-items-center">

        @if (config('layout.aside.self.display'))
            <button class="btn p-0 burger-icon burger-icon-left" id="kt_aside_mobile_toggle"><span></span></button>
        @endif

        <button class="btn btn-hover-text-primary p-0 ml-2" id="kt_header_mobile_topbar_toggle">
            {{ Metronic::getSVG('media/svg/icons/General/User.svg', 'svg-icon-xl') }}
        </button>

    </div>
</div>
