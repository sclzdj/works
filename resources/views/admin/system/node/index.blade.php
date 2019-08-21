@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/jquery-nestable/jquery.nestable.css').'?'.$SFV}}" type="text/css"/>
@endsection
@section('content')
    <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <p><strong><i class="fa fa-fw fa-hand-o-right"></i> 说明：</strong>本系统最高支持4级；1级节点服务顶部导航模块，2级节点服务左侧一级菜单，3级节点服务左侧二级菜单，4级节点及之后均服务于页面操作方法；系统模块和系统主页节点是系统专属节点不可被操作，系统主页节点不能有子节点</p>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <ul class="nav nav-tabs">
                    <li class="@if($pid==0) active @endif">
                        <a href="{{action('Admin\System\NodeController@index')}}">全部</a>
                    </li>
                    @foreach($modules as $module)
                        <li class="@if($pid==$module->id) active @endif">
                            <a href="{{action('Admin\System\NodeController@index',['pid'=>$module->id])}}">{{$module->name}}</a>
                        </li>
                    @endforeach
                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\NodeController@moduleSort'))
                        <li>
                            <a href="{{action('Admin\System\NodeController@moduleSort')}}">模块排序</a>
                        </li>
                    @endif
                    <li class="pull-right">
                        <ul class="block-options push-10-t push-10-r">
                            <li>
                                <button type="button" data-toggle="block-option" data-action="fullscreen_toggle"><i class="si si-size-fullscreen"></i></button>
                            </li>
                            <li>
                                <button type="button" data-toggle="block-option" data-action="refresh_toggle" data-action-mode="demo"><i class="si si-refresh"></i></button>
                            </li>
                            <li>
                                <button type="button" data-toggle="block-option" data-action="content_toggle"><i class="si si-arrow-up"></i></button>
                            </li>
                            <li>
                                <button type="button" data-toggle="block-option" data-action="close"><i class="si si-close"></i></button>
                            </li>
                        </ul>
                    </li>
                </ul>
                <div class="block-content tab-content">
                    <div class="alert alert-warning alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <p><strong><i class="fa fa-fw fa-info-circle"></i> 提示：</strong>按住表头可拖动节点，调整后点击【保存排序】，启用节点拖动到禁用节点下那么它也将被禁用；启用节点会连它的<span class="text-primary">所有长辈节点和所有后代节点</span>一起启用；禁用节点会连它的<span class="text-primary">所有后代节点</span>一起禁用</p>
                    </div>
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="toolbar-btn-action">
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\NodeController@create'))
                                        <a title="添加@if($pid)节点@else模块@endif" class="btn btn-primary" href="{{action('Admin\System\NodeController@create',['pid'=>$pid])}}"><i class="fa fa-plus-circle"></i> 添加@if($pid)节点@else模块@endif</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\NodeController@sort'))
                                        <button title="保存排序" type="button" class="btn btn-default disabled" href="{{action('Admin\System\NodeController@sort')}}" submit-type="POST" id="save" disabled=""><i class="fa fa-check-circle-o"></i> 保存排序</button>
                                    @endif
                                    <button title="隐藏禁用节点" type="button" class="btn btn-danger" id="hide_disable"><i class="fa fa-eye-slash"></i> 隐藏禁用节点</button>
                                    <button title="显示禁用节点" type="button" class="btn btn-info" id="show_disable"><i class="fa fa-eye"></i> 显示禁用节点</button>
                                    <button title="展开所有节点" type="button" class="btn btn-success" id="expand-all"><i class="fa fa-plus"></i> 展开所有节点</button>
                                    <button title="收起所有节点" type="button" class="btn btn-warning" id="collapse-all"><i class="fa fa-minus"></i> 收起所有节点</button>
                                    <span class="form-inline">
                                        <input class="form-control" type="text" value="@if($max_level!=0){{$max_level}}@endif" placeholder="显示层数，回车键确认" title="显示层数，回车键确认" onkeyup="if(event.keyCode==13){location.href='{{action('Admin\System\NodeController@index')}}?max_level='+this.value+'&pid={{$pid}}';}">
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="dd" id="node_list" pid="{{$pid}}">
                            <ol class="dd-list">
                                {!! $grMaxHtml !!}
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script src="{{asset('/static/libs/jstree/jstree.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/jquery-nestable/jquery.nestable.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/node-sort-submit.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/jquery-ui/jquery-ui.min.js').'?'.$SFV}}"></script>
    <script>
        $(function () {

        });
    </script>
@endsection
