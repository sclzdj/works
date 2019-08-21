@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/jquery-nestable/jquery.nestable.css').'?'.$SFV}}" type="text/css"/>
@endsection
@section('css')
    <style>
        #area_list .list-link {
            font-size: 12px;
            font-weight: normal;
            display: none;
        }
    </style>
@endsection
@section('content')
    <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <p><strong><i class="fa fa-fw fa-hand-o-right"></i> 说明：</strong>本系统最高支持4级地区</p>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="javascript:void(0);">
                            @if($pid>0)
                                @if(count($elderAreas)>0)
                                    @php
                                        $elderArea_names=[];
                                        foreach ($elderAreas as $area){
                                            $elderArea_names[]=$area['short_name'];
                                        }
                                        $elderArea_names=implode('<span style="font-weight:normal;font-size:12px;">&nbsp;&nbsp;>&nbsp;&nbsp;</span>',$elderArea_names);
                                    @endphp
                                    {!! $elderArea_names !!}
                                @else
                                    地区管理
                                @endif
                            @else
                                省份管理
                            @endif
                        </a>
                    </li>
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
                        <p><strong><i class="fa fa-fw fa-info-circle"></i> 提示：</strong>按住表头可拖动地区，调整后点击【保存排序】</p>
                    </div>
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="toolbar-btn-action">
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\AreaController@sort'))
                                        <button title="保存排序" type="button" class="btn btn-default disabled" href="{{action('Admin\System\AreaController@sort')}}" submit-type="POST" id="save" disabled=""><i class="fa fa-check-circle-o"></i> 保存排序</button>
                                    @endif
                                    @if($pid>0)
                                        <button type="button" class="btn btn-default pull-right" onclick="javascript:history.back(-1);return false;">返回</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="dd" id="area_list" pid="{{$pid}}">
                            <ol class="dd-list">
                                {!! $grMaxHtml !!}
                            </ol>
                        </div>
                        @if($pid>0)
                            <div class="block text-center center-align" style="margin-top: 20px;">
                                <button class="btn btn-default" type="button" onclick="javascript:history.back(-1);return false;">
                                    返回
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script src="{{asset('/static/libs/jstree/jstree.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/jquery-nestable/jquery.nestable.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/area-sort-submit.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/jquery-ui/jquery-ui.min.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            $(document).delegate('#area_list .dd-item', 'mouseover', function () {
                $(this).find('.list-link').show();
                $(this).mouseout(function () {
                    $(this).find('.list-link').hide();
                });
            });
        });
    </script>
@endsection
