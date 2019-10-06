@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/bootstrap3-editable/css/bootstrap-editable.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('/static/libs/bootstrap-datepicker/bootstrap-datepicker3.min.css').'?'.$SFV}}">
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <div class="block-header bg-gray-lighter">
                    <ul class="block-options">
                        <li>
                            <button type="button" class="page-reload"><i class="si si-refresh"></i></button>
                        </li>
                        <li>
                            <button type="button" data-toggle="block-option" data-action="fullscreen_toggle"><i
                                    class="si si-size-fullscreen"></i></button>
                        </li>
                    </ul>
                    <h3 class="block-title">使用帮助管理</h3>
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="pull-left toolbar-btn-action">
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\HelpNoteController@create'))
                                        <a class="btn btn-primary btn-table-top"
                                           href="{{action('Admin\Works\HelpNoteController@create')}}"><i
                                                class="fa fa-plus-circle"></i> 添加</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\HelpNoteController@destroy'))
                                        <a class="btn btn-danger btn-table-top ids-submit" submit-type="DELETE"
                                           href="{{action('Admin\Works\HelpNoteController@destroy',['id'=>0])}}"
                                           confirm="<div class='text-center'>确定要删除吗？</div>"><i
                                                class="fa fa-times-circle-o"></i> 删除</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\HelpNoteController@sort'))
                                        <a class="btn btn-default btn-table-top"
                                           href="{{action('Admin\Works\HelpNoteController@sort')}}">排序</a>
                                    @endif
                                </div>
                                <form action="{{action('Admin\Works\HelpNoteController@index')}}" method="get">
                                    <input type="hidden" name="order_field" value="{{$orderBy['order_field']}}">
                                    <input type="hidden" name="order_type" value="{{$orderBy['order_type']}}">
                                    <div class="pull-right text-right">
                                        <div class="search-bar search-bar-130" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">ID</div>
                                                <input type="text" class="form-control" value="{{$filter['id']}}"
                                                       name="id" placeholder="请输入ID">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-150" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">标题</div>
                                                <input type="text" class="form-control" value="{{$filter['title']}}"
                                                       name="title" placeholder="请输入标题">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-150" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">内容</div>
                                                <input type="text" class="form-control" value="{{$filter['content']}}"
                                                       name="content" placeholder="请输入内容">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-340" style="display: inline-block">
                                            <div class="input-daterange input-group" data-date-format="yyyy-mm-dd">
                                                <span class="input-group-addon" style="border-width:1px;">创建日期</span>
                                                <input class="form-control" type="text"
                                                       value="{{$filter['created_at_start']}}" name="created_at_start"
                                                       placeholder="开始日期" autocomplete="off">
                                                <span class="input-group-addon"><i
                                                        class="fa fa-chevron-right"></i></span>
                                                <input class="form-control" type="text"
                                                       value="{{$filter['created_at_end']}}" name="created_at_end"
                                                       placeholder="结束日期" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-submit"
                                             style="display: inline-block;width: auto;margin-right: 0;">
                                            <div class="input-group">
                                                <button type="submit" class="btn btn-default">搜索</button>
                                                <a href="{{action('Admin\Works\HelpNoteController@index',array_merge($orderBy,['pageSize'=>$pageInfo['pageSize']]))}}"
                                                   class="btn btn-default" style="margin-left: 5px;">重置</a>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="pageSize" value="{{$pageInfo['pageSize']}}">
                                </form>
                            </div>
                        </div>
                        <div class="builder-table-wrapper">
                            <div class="builder-table" id="builder-table">
                                <div class="builder-table-head" id="builder-table-head">
                                    <table
                                        class="table table-builder table-hover table-bordered table-striped js-table-checkable"
                                        style="width: 1571px;">
                                        <colgroup>
                                            <col width="50">
                                            <col class="" width="50">
                                            <col class="">
                                            <col class="">
                                            <col class="" width="80">
                                            <col class="" width="160">
                                            <col class="" width="100">
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th class="text-center">
                                                <label
                                                    class="css-input css-checkbox css-checkbox-primary remove-margin-t remove-margin-b">
                                                    <input type="checkbox" id="check-all"><span></span>
                                                </label>
                                            </th>
                                            <th class="">
                                                ID
                                                @if($orderBy['order_field']=='id')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'id','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'id','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'id'],$pageInfo))}}"
                                                            data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                帮助标题<span></span>
                                            </th>
                                            <th class="">
                                                帮助内容<span></span>
                                            </th>
                                            <th class="">
                                                序号
                                                @if($orderBy['order_field']=='sort')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'sort','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'sort','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'sort'],$pageInfo))}}"
                                                            data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                创建时间
                                                @if($orderBy['order_field']=='created_at')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\HelpNoteController@index',array_merge($filter,['order_field'=>'created_at'],$pageInfo))}}"
                                                            data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                操作<span></span>
                                            </th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="builder-table-body">
                                    <table
                                        class="table table-builder table-hover table-bordered table-striped js-table-checkable-target"
                                        id="builder-table-main">
                                        <colgroup>
                                            <col width="50">
                                            <col width="50" class="">
                                            <col class="">
                                            <col class="">
                                            <col width="80" class="">
                                            <col width="160" class="">
                                            <col width="100" class="">
                                        </colgroup>
                                        <tbody>
                                        @forelse ($helpNotes as $key=>$helpNote)
                                            <tr class="">
                                                <td class="text-center">
                                                    <div class="table-cell">
                                                        <label class="css-input css-checkbox css-checkbox-primary">
                                                            <input class="ids" type="checkbox" name="ids[]"
                                                                   value="{{$helpNote->id}}"><span></span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$helpNote->id}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell" data-toggle="tooltip"
                                                         data-original-title="{{$helpNote->title}}">
                                                        {{$helpNote->title}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell" data-toggle="tooltip"
                                                         data-original-title="{{$helpNote->content}}">
                                                        {{$helpNote->content}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$helpNote->sort}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$helpNote->created_at}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        <div class="btn-group">
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\HelpNoteController@edit'))
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\HelpNoteController@edit',['id'=>$helpNote->id])}}">修改</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\HelpNoteController@destroy'))
                                                                <a class="btn btn-xs btn-default id-submit"
                                                                   submit-type="DELETE"
                                                                   href="{{action('Admin\Works\HelpNoteController@destroy',['id'=>$helpNote->id])}}"
                                                                   confirm="<div class='text-center'>确定要删除吗？</div>">删除</a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="table-empty">
                                                <td class="text-center empty-info" colspan="9">
                                                    <i class="fa fa-database"></i> 暂无数据<br>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="data-table-toolbar">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pagination-info pull-left">
                                        {!! $helpNotes->appends(array_merge($filter,$orderBy,['pageSize'=>$pageInfo['pageSize']]))->links() !!}
                                    </div>
                                    <div class="pagination-info pull-right">
                                        <div>
                                            @php
                                                $pageUrl=action('Admin\Works\HelpNoteController@index',array_merge($filter,$orderBy));
                                                if(strpos($pageUrl,'?') !== false){
                                                        $pageUrl=$pageUrl.'&';
                                                }else{
                                                        $pageUrl=$pageUrl.'?';
                                                }
                                            @endphp
                                            <input type="text" class="form-control input-sm go-page" title="回车跳转"
                                                   value="{{$helpNotes->currentPage()}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'page='+this.value+'&pageSize={{$pageInfo['pageSize']}}';}">
                                            / <strong>{{$helpNotes->lastPage()}}</strong> 页，共
                                            <strong>{{$helpNotes->total()}}</strong> 条数据，每页显示数量
                                            <input type="text" class="form-control input-sm nums" id="pageSize"
                                                   title="回车确定" value="{{$pageInfo['pageSize']}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'pageSize='+this.value+'&page={{$pageInfo['page']}}';}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script src="{{asset('/static/libs/bootstrap3-editable/js/bootstrap-editable.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/table-init.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/table-submit.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/bootstrap-datepicker/bootstrap-datepicker.min.js').'?'.$SFV}}"></script>
    <script
        src="{{asset('/static/libs/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            App.initHelpers(["datepicker"]);
        })
    </script>
@endsection
