<div class="bg-gray-lighter" id="location-navbar">
    <ol class="breadcrumb">
        <li><i class="fa fa-map-marker"></i></li>
        @php
            $location=\App\Servers\NavigationServer::location();
        @endphp
        @foreach($location as $key=>$systemNode)
            @if($key==0)
            <li><a class="link-effect" href="@if($systemNode['action']!==''){{action($systemNode['action'])}} @elseif(\App\Servers\NavigationServer::moduleUrl($systemNode['id'])!=='') {{\App\Servers\NavigationServer::moduleUrl($systemNode['id'])}} @else javascript:void(0); @endif">{{$systemNode['name']}}</a></li>
            @elseif(count($location)!=$key+1)
                <li><a class="link-effect" href="@if($systemNode['action']!==''){{action($systemNode['action'])}} @else javascript:void(0); @endif">{{$systemNode['name']}}</a></li>
            @else
                <li><a class="link-effect" href="javascript:void(0);">{{$systemNode['name']}}</a></li>
            @endif
        @endforeach
    </ol>
</div>
