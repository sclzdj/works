<nav id="sidebar">
    <!-- Sidebar Scroll Container -->
    <div id="sidebar-scroll">
        <!-- Sidebar Content -->
        <!-- Adding .sidebar-mini-hide to an element will hide it when the sidebar is in mini mode -->
        <div class="sidebar-content">
            <!-- Side Header -->
            <div class="side-header side-content bg-white-op dolphin-header">
                <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
                <button class="btn btn-link text-gray pull-right hidden-md hidden-lg" type="button" data-toggle="layout" data-action="sidebar_close">
                    <i class="fa fa-times"></i>
                </button>
                <a class="h5 text-white" href="{{\App\Servers\NavigationServer::homeUrl()}}">
                    @php
                        $admin_logo=\App\Model\Admin\SystemConfig::getVal('admin_logo','admin');
                    @endphp
                    <img src="{{$admin_logo!==''?$admin_logo:asset('/static/admin/img/logo.png').'?'.$SFV}}" class="logo" alt="项目LOGO">
                    @php
                        $admin_logo_text=\App\Model\Admin\SystemConfig::getVal('admin_logo_text','admin');
                    @endphp
                    <img src="{{$admin_logo_text!==''?$admin_logo_text:asset('/static/admin/img/logo-text.png').'?'.$SFV}}" class="logo-text sidebar-mini-hide" alt="项目文字LOGO">
                </a>
            </div>
            <!-- END Side Header -->
            <!-- Side Content -->
            <div class="side-content" id="sidebar-menu">
                <ul class="nav-main" id="nav-236">
                    @foreach(\App\Servers\NavigationServer::menus() as $menu)
                        <li class="du-menu-status">
                            @if($menu['action']==='' && $menu['_data'])
                                <a class="nav-submenu @if(\App\Servers\NavigationServer::activeMenu($menu['action'])) active @endif" data-toggle="nav-submenu" href="javascript:void(0);"><i class="{{$menu['icon']}}"></i><span class="sidebar-mini-hide">{{$menu['name']}}</span></a>
                                <ul>
                                    @foreach($menu['_data'] as $m)
                                        <li>
                                            <a class="@if(\App\Servers\NavigationServer::activeMenu($m['action'])) active @endif" href="@if($m['action']!=='') {{action($m['action'])}} @else javascript:void(0); @endif" target="_self"><i class="{{$m['icon']}}"></i>{{$m['name']}}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a class=" @if(\App\Servers\NavigationServer::activeMenu($menu['action'])) active @endif" href="@if($menu['action']!=='') {{action($menu['action'])}} @else javascript:void(0); @endif" target="_self"><i class="{{$menu['icon']}}"></i><span class="sidebar-mini-hide">{{$menu['name']}}</span></a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <!-- END Side Content -->
        </div>
        <!-- Sidebar Content -->
    </div>
    <!-- END Sidebar Scroll Container -->
</nav>
