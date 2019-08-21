<header id="header-navbar" class="content-mini content-mini-full">
    <!-- Header Navigation Right -->
    <ul class="nav-header pull-right">
        <li>
            <div class="btn-group">
                <button class="btn btn-default btn-image dropdown-toggle" data-toggle="dropdown" type="button">
                    <img src="@if(auth('admin')->user()->avatar!=='') {{auth('admin')->user()->avatar}} @else {{asset('/static/admin/img/avatar.jpg').'?'.$SFV}} @endif" alt="用户头像">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li class="dropdown-header">@if(auth()->user()->nickname!=='') {{auth()->user()->nickname}} @else {{auth()->user()->username}} @endif
                        @if(auth()->user()->type==0) (超级管理员)@elseif(auth()->user()->type==1) (角色权限)@elseif(auth()->user()->type==2) (直赋权限)@endif</li>
                    <li>
                        @if(\App\Servers\PermissionServer::allowAction('Admin\System\IndexController@config'))
                            <a tabindex="-1" href="{{action('Admin\System\IndexController@config')}}">
                                <i class="si si-settings pull-right"></i>系统配置
                            </a>
                        @endif
                        @if(\App\Servers\PermissionServer::allowAction('Admin\System\IndexController@updatePassword'))
                            <a tabindex="-1" href="{{action('Admin\System\IndexController@updatePassword')}}">
                                <i class="si si-wrench pull-right"></i>修改密码
                            </a>
                        @endif
                        @if(\App\Servers\PermissionServer::allowAction('Admin\System\IndexController@setInfo'))
                            <a tabindex="-1" href="{{action('Admin\System\IndexController@setInfo')}}">
                                <i class="si si-user pull-right"></i>资料设置
                            </a>
                        @endif
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a tabindex="-1" href="javascript:void(0);" id="admin-logout">
                            <i class="si si-logout pull-right"></i>退出帐号
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @php
            $indexUrl=\App\Model\Admin\SystemConfig::indexUrl();
        @endphp
        @if($indexUrl)
            <li>
                <a class="btn btn-default" href="{{$indexUrl}}" target="_blank" data-toggle="tooltip" data-placement="bottom" data-original-title="打开前台">
                    <i class="fa fa-external-link-square"></i>
                </a>
            </li>
        @endif
        <li>
            <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
            <button class="btn btn-default" data-toggle="layout" data-action="side_overlay_toggle" title="侧边栏" type="button">
                <i class="fa fa-tasks"></i>
            </button>
        </li>
        <li></li>
    </ul>
    <!-- END Header Navigation Right -->
    <!-- Header Navigation Left -->
    <ul class="nav nav-pills pull-left">
        <li class="hidden-md hidden-lg">
            <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
            <a href="javascript:void(0)" data-toggle="layout" data-action="sidebar_toggle"><i class="fa fa-navicon"></i></a>
        </li>
        <li class="hidden-xs hidden-sm">
            <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
            <a href="javascript:void(0)" title="打开/关闭左侧导航" data-toggle="layout" data-action="sidebar_mini_toggle"><i class="fa fa-bars"></i></a>
        </li>
        @foreach(\App\Servers\NavigationServer::modules() as $module)
            <li class="hidden-xs hidden-sm @if($module['id']==\App\Servers\NavigationServer::currentModuleId()) active @endif">
                <a href="{{$module['url']}}" target="_self" class=""><i class="{{$module['icon']}}"></i>{{$module['name']}}</a>
            </li>
        @endforeach

        <li>
            <!-- Opens the Apps modal found at the bottom of the page, before including JS code -->
            <a href="#" data-toggle="modal" data-target="#apps-modal"><i class="si si-grid"></i></a>
        </li>
    </ul>
    <!-- END Header Navigation Left -->
</header>


