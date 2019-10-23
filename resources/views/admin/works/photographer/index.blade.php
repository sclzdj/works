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
                    <h3 class="block-title">摄影师管理</h3>
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="pull-left toolbar-btn-action">
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerController@destroy'))
                                        <a class="btn btn-danger btn-table-top ids-submit" submit-type="DELETE"
                                           href="{{action('Admin\Works\PhotographerController@destroy',['id'=>0])}}"
                                           confirm="<div class='text-center'>确定要删除吗？</div>"><i
                                                class="fa fa-times-circle-o"></i> 删除</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@index'))
                                        <a class="btn btn-default btn-table-top"
                                           href="{{action('Admin\Works\PhotographerWorkController@index')}}">作品集管理</a>
                                    @endif
                                </div>
                                <form action="{{action('Admin\Works\PhotographerController@index')}}" method="get">
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
                                                <div class="input-group-addon">姓名</div>
                                                <input type="text" class="form-control" value="{{$filter['name']}}"
                                                       name="name" placeholder="请输入姓名">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-200" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">手机号</div>
                                                <input type="text" class="form-control" value="{{$filter['mobile']}}"
                                                       name="mobile" placeholder="请输入手机号">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-200" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">微信号</div>
                                                <input type="text" class="form-control" value="{{$filter['wechat']}}"
                                                       name="wechat" placeholder="请输入微信号">
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
                                        <br>
                                        <div class="search-bar search-bar-300" style="display: inline-block">
                                            <div class="input-group" style="width: 100%;">
                                                <div class="input-group-addon">地区</div>
                                                <select class="form-control area-select-box-1 area-select-box-province"
                                                        name="province" level="1">
                                                    <option value="">全部省份</option>
                                                    @foreach($provinces as $systemArea)
                                                        <option value="{{$systemArea->id}}"
                                                                @if($filter['province']==$systemArea->id) selected @endif>
                                                            {{$systemArea->name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select class="form-control area-select-box-1 area-select-box-city"
                                                        name="city" level="2">
                                                    <option value="">全部城市</option>
                                                    @foreach($cities as $systemArea)
                                                        <option value="{{$systemArea->id}}"
                                                                @if($filter['city']==$systemArea->id) selected @endif>
                                                            {{$systemArea->name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select class="form-control area-select-box-1 area-select-box-area"
                                                        name="area" level="3">
                                                    <option value="">全部地方</option>
                                                    @foreach($areas as $systemArea)
                                                        <option value="{{$systemArea->id}}"
                                                                @if($filter['area']==$systemArea->id) selected @endif>
                                                            {{$systemArea->name}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-230" style="display: inline-block">
                                            <div class="input-group" style="width: 100%;">
                                                <input type="hidden" name="photographer_rank_id"
                                                       value="{{$filter['photographer_rank_id']}}"
                                                       class="photographer-rank-box-1 hidden-value">
                                                <div class="input-group-addon">头衔</div>
                                                <select
                                                    class="form-control photographer-rank-box-1 photographer-rank-level1"
                                                    level="1">
                                                    <option value="">全部</option>
                                                </select>
                                                <select
                                                    class="form-control photographer-rank-box-1 photographer-rank-level2"
                                                    level="2">
                                                    <option value="">全部</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-submit"
                                             style="display: inline-block;width: auto;margin-right: 0;">
                                            <div class="input-group">
                                                <button type="submit" class="btn btn-default">搜索</button>
                                                <a href="{{action('Admin\Works\PhotographerController@index',array_merge($orderBy,['pageSize'=>$pageInfo['pageSize']]))}}"
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
                                            <col class="" width="100">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="" width="160">
                                            <col class="" width="230">
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
                                                                href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'id','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'id','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'id'],$pageInfo))}}"
                                                            data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                用户昵称<span></span>
                                            </th>
                                            <th class="">
                                                姓名<span></span>
                                            </th>
                                            <th class="">
                                                地区<span></span>
                                            </th>
                                            <th class="">
                                                头衔<span></span>
                                            </th>
                                            <th class="">
                                                手机号<span></span>
                                            </th>
                                            <th class="">
                                                微信号<span></span>
                                            </th>
                                            <th class="">
                                                作品集数量<span></span>
                                            </th>
                                            <th class="">
                                                创建时间
                                                @if($orderBy['order_field']=='created_at')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\PhotographerController@index',array_merge($filter,['order_field'=>'created_at'],$pageInfo))}}"
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
                                            <col class="" width="100">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="">
                                            <col class="" width="160">
                                            <col class="" width="230">
                                        </colgroup>
                                        <tbody>
                                        @forelse ($photographers as $key=>$photographer)
                                            <tr class="">
                                                <td class="text-center">
                                                    <div class="table-cell">
                                                        <label class="css-input css-checkbox css-checkbox-primary">
                                                            <input class="ids" type="checkbox" name="ids[]"
                                                                   value="{{$photographer->id}}"><span></span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer->id}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer['user']->nickname}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer->name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer['province']->short_name.' '.$photographer['city']->short_name.' '.$photographer['area']->short_name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer['rank']->name}}摄影师
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer->mobile}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer->wechat}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer['works_count']}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographer->created_at}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        <div class="btn-group">
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerController@gallery'))
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\PhotographerController@gallery',['id'=>$photographer->id])}}" target="_blank">图库</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerController@poster'))
                                                            <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\PhotographerController@poster',['id'=>$photographer->id])}}" target="_blank">海报</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerController@edit'))
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\PhotographerController@edit',['id'=>$photographer->id])}}">修改</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerController@destroy'))
                                                                <a class="btn btn-xs btn-default id-submit"
                                                                   submit-type="DELETE"
                                                                   href="{{action('Admin\Works\PhotographerController@destroy',['id'=>$photographer->id])}}"
                                                                   confirm="<div class='text-center'>确定要删除吗？</div>">删除</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@index'))
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\PhotographerWorkController@index',['photographer_id'=>$photographer->id])}}">作品集</a>
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
                                        {!! $photographers->appends(array_merge($filter,$orderBy,['pageSize'=>$pageInfo['pageSize']]))->links() !!}
                                    </div>
                                    <div class="pagination-info pull-right">
                                        <div>
                                            @php
                                                $pageUrl=action('Admin\Works\PhotographerController@index',array_merge($filter,$orderBy));
                                                if(strpos($pageUrl,'?') !== false){
                                                        $pageUrl=$pageUrl.'&';
                                                }else{
                                                        $pageUrl=$pageUrl.'?';
                                                }
                                            @endphp
                                            <input type="text" class="form-control input-sm go-page" title="回车跳转"
                                                   value="{{$photographers->currentPage()}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'page='+this.value+'&pageSize={{$pageInfo['pageSize']}}';}">
                                            / <strong>{{$photographers->lastPage()}}</strong> 页，共
                                            <strong>{{$photographers->total()}}</strong> 条数据，每页显示数量
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
            $(document).on('change', '.area-select-box-1', function () {
                var className = '.area-select-box-1';
                var level = $(this).attr('level');
                var value = $(this).val();
                var run = function (url, data, callback) {
                    $.ajax({
                        type: 'GET',
                        url: url,
                        dataType: 'JSON',
                        data: data,
                        success: function (response) {
                            callback(response);
                        },
                        error: function (xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);
                            if (xhr.status == 422) { //数据指定错误，错误码固定为422
                                var validate_notify = '';
                                $.each(response.errors, function (k, v) {
                                    var validate_tips = '';
                                    for (var i in v) {
                                        validate_tips += '<div class="col-md-11 col-md-offset-1 form-validate-msg form-option-line"><i class="fa fa-fw fa-warning text-warning"></i>' + v[i] + '</div>';
                                        validate_notify += '<li>' + v[i] + '</li>';
                                    }
                                    $('#create-' + k).append(validate_tips); // 页面表单项下方提示，错误验证信息
                                });
                                Dolphin.notify(validate_notify, 'danger'); //页面顶部浮窗提示，错误验证信息
                            } else if (xhr.status == 419) { // csrf错误，错误码固定为419
                                Dolphin.notify('请勿重复请求~', 'danger');
                            } else {
                                if (response.message) {
                                    Dolphin.notify(response.message, 'danger');
                                } else {
                                    Dolphin.notify('服务器错误~', 'danger');
                                }
                            }
                        }
                    });
                };
                var city_html = '<option value="">全部城市</option>';
                var area_html = '<option value="">全部地方</option>';
                if (level == 1) {
                    if (value === '') {
                        $(className + '.area-select-box-city').html(city_html);
                        $(className + '.area-select-box-area').html(area_html);
                    } else {
                        var url = '/api/getCitys';
                        var data = {province_id: value};
                        run(url, data, function (data) {
                            $(data.data).each(function (k, v) {
                                city_html += '<option value="' + v.id + '">' + v.name + '</option>';
                            });
                            $(className + '.area-select-box-city').html(city_html);
                            $(className + '.area-select-box-area').html(area_html);
                        });
                    }
                } else if (level == 2) {
                    if (value === '') {
                        $(className + '.area-select-box-area').html(area_html);
                    } else {
                        var url = '/api/getAreas';
                        var data = {city_id: value};
                        run(url, data, function (data) {
                            $(data.data).each(function (k, v) {
                                area_html += '<option value="' + v.id + '">' + v.name + '</option>';
                            });
                            $(className + '.area-select-box-area').html(area_html);
                        });
                    }
                } else {
                    return false;
                }
            });

            function photographerRankInit() {
                var photographerRanks ={!! json_encode($photographerRanks) !!};
                var photographer_rank_id ={!! intval($filter['photographer_rank_id']) !!};
                var html1 = '<option value="">全部</option>';
                var html2 = [];
                var html2_0 = '<option value="">全部</option>';
                var html2_init = html2_0;
                var html2_select_index = 0;
                $(photographerRanks).each(function (k, v) {
                    var selected_html1 = '';
                    if (v.id == photographer_rank_id) {
                        selected_html1 = 'selected';
                        html2_select_index = v.id;
                    }
                    html2[v.id] = html2_0;
                    $(v.children).each(function (_k, _v) {
                        var selected_html2 = '';
                        if (_v.id == photographer_rank_id) {
                            selected_html1 = 'selected';
                            selected_html2 = 'selected';
                            html2_select_index = v.id;
                        }
                        html2[v.id] += '<option value="' + _v.id + '" ' + selected_html2 + '>' + _v.name + '</option>';
                    });
                    html1 += '<option value="' + v.id + '" ' + selected_html1 + '>' + v.name + '</option>';
                });
                html2_init = html2[html2_select_index];
                $('.photographer-rank-box-1.photographer-rank-level1').html(html1);
                $('.photographer-rank-box-1.photographer-rank-level2').html(html2_init);
                $(document).on('change', '.photographer-rank-box-1', function () {
                    var className = '.photographer-rank-box-1';
                    var level = $(this).attr('level');
                    var value = $(this).val();
                    if (level == 1) {
                        if (value === '') {
                            $(className + '.photographer-rank-level2').html(html2_0);
                        } else {
                            $(className + '.photographer-rank-level2').html(html2[value]);
                        }
                        $(className + '.hidden-value').val(value);
                    } else if (level == 2) {
                        if (value === '') {
                            $(className + '.hidden-value').val($(className + '.photographer-rank-level1 option:checked').val());
                        } else {
                            $(className + '.hidden-value').val(value);
                        }
                    } else {
                        return false;
                    }
                });
            }

            photographerRankInit();
        });
    </script>
@endsection
