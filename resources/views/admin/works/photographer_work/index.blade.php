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
                    @if($photographer['id']>0)
                        <h3 class="block-title"><span style="color: #f00000">{{$photographer['name']}}</span>摄影师的作品集管理
                            <a href="{{action('Admin\Works\PhotographerWorkController@index')}}"
                               style="font-size: 12px;">[全部摄影师]</a>
                        </h3>
                    @else
                        <h3 class="block-title">作品集管理</h3>
                    @endif
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="pull-left toolbar-btn-action">
                                    @if($photographer['id']>0)
                                        @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@create'))
                                            <a class="btn btn-primary btn-table-top"
                                               href="{{action('Admin\Works\PhotographerWorkController@create',['photographer_id'=>$photographer['id']])}}"><i
                                                    class="fa fa-plus-circle"></i> 添加</a>
                                        @endif
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@destroy'))
                                        <a class="btn btn-danger btn-table-top ids-submit" submit-type="DELETE"
                                           href="{{action('Admin\Works\PhotographerWorkController@destroy',['id'=>0])}}"
                                           confirm="<div class='text-center'>确定要删除吗？</div>"><i
                                                class="fa fa-times-circle-o"></i> 删除</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\Works\photographerWorkontroller@index'))
                                        <a class="btn btn-default btn-table-top"
                                           href="{{action('Admin\Works\photographerWorkontroller@index')}}">作品集管理</a>
                                    @endif
                                </div>
                                <form
                                    action="{{action('Admin\Works\PhotographerWorkController@index')}}"
                                    method="get">
                                    <input type="hidden" name="photographer_id" value="{{$photographer['id']}}">
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
                                        <div class="search-bar search-bar-230" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">客户名称</div>
                                                <input type="text" class="form-control"
                                                       value="{{$filter['customer_name']}}"
                                                       name="customer_name" placeholder="请输入客户名称">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-150" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">标签</div>
                                                <input type="text" class="form-control" value="{{$filter['tag_name']}}"
                                                       name="tag_name" placeholder="请输入标签">
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
                                        <div class="search-bar search-bar-230" style="display: inline-block">
                                            <div class="input-group" style="width: 100%;">
                                                <input type="hidden" name="photographer_work_customer_industry_id"
                                                       value="{{$filter['photographer_work_customer_industry_id']}}"
                                                       class="photographer-work-customer-industry-box-1 hidden-value">
                                                <div class="input-group-addon">客户行业</div>
                                                <select
                                                    class="form-control photographer-work-customer-industry-box-1 photographer-work-customer-industry-level1"
                                                    level="1">
                                                    <option value="">全部</option>
                                                </select>
                                                <select
                                                    class="form-control photographer-work-customer-industry-box-1 photographer-work-customer-industry-level2"
                                                    level="2">
                                                    <option value="">全部</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-230" style="display: inline-block">
                                            <div class="input-group" style="width: 100%;">
                                                <input type="hidden" name="photographer_work_category_id"
                                                       value="{{$filter['photographer_work_category_id']}}"
                                                       class="photographer-work-category-box-1 hidden-value">
                                                <div class="input-group-addon">分类</div>
                                                <select
                                                    class="form-control photographer-work-category-box-1 photographer-work-category-level1"
                                                    level="1">
                                                    <option value="">全部</option>
                                                </select>
                                                <select
                                                    class="form-control photographer-work-category-box-1 photographer-work-category-level2"
                                                    level="2">
                                                    <option value="">全部</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-submit"
                                             style="display: inline-block;width: auto;margin-right: 0;">
                                            <div class="input-group">
                                                <button type="submit" class="btn btn-default">搜索</button>
                                                <a href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($orderBy,['pageSize'=>$pageInfo['pageSize'],'photographer_id'=>$photographer['id']]))}}"
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
                                            <col class="">
                                            <col class="" width="160">
                                            <col class="" width="140">
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
                                                                href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'id','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'id','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'id'],$pageInfo))}}"
                                                            data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                摄影师<span></span>
                                            </th>
                                            <th class="">
                                                客户名称<span></span>
                                            </th>
                                            <th class="">
                                                客户行业<span></span>
                                            </th>
                                            <th class="">
                                                项目金额<span></span>
                                            </th>
                                            <th class="">
                                                成片张数<span></span>
                                            </th>
                                            <th class="">
                                                拍摄时长<span></span>
                                            </th>
                                            <th class="">
                                                分类<span></span>
                                            </th>
                                            <th class="">
                                                标签<span></span>
                                            </th>
                                            <th class="">
                                                创建时间
                                                @if($orderBy['order_field']=='created_at')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'desc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击降序"
                                                                alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a
                                                                href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'asc'],$pageInfo))}}"
                                                                data-toggle="tooltip" data-original-title="点击升序"
                                                                alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a
                                                            href="{{action('Admin\Works\PhotographerWorkController@index',array_merge($filter,['order_field'=>'created_at'],$pageInfo))}}"
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
                                            <col class="">
                                            <col class="" width="160">
                                            <col class="" width="140">
                                        </colgroup>
                                        <tbody>
                                        @forelse ($photographerWorks as $key=>$photographerWork)
                                            <tr class="">
                                                <td class="text-center">
                                                    <div class="table-cell">
                                                        <label class="css-input css-checkbox css-checkbox-primary">
                                                            <input class="ids" type="checkbox" name="ids[]"
                                                                   value="{{$photographerWork->id}}"><span></span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->id}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork['photographer']->name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->customer_name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork['customer_industry']->name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->project_amount}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->sheets_number}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->shooting_duration}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork['category']->name}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell" data-toggle="tooltip"
                                                         data-original-title="{{\App\Servers\ArrServer::ids($photographerWork['tags']->toArray(),'name','1')}}">
                                                        {{\App\Servers\ArrServer::ids($photographerWork['tags']->toArray(),'name','1')}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$photographerWork->created_at}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        <div class="btn-group">
                                                            @php
                                                                $poster=\App\Model\Index\PhotographerWork::poster($photographerWork->id);
                                                            @endphp
                                                            @if($poster['code']==200)
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{$poster['url']}}" target="_blank">海报</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@edit'))
                                                                <a class="btn btn-xs btn-default"
                                                                   href="{{action('Admin\Works\PhotographerWorkController@edit',['id'=>$photographerWork->id])}}">修改</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\Works\PhotographerWorkController@destroy'))
                                                                <a class="btn btn-xs btn-default id-submit"
                                                                   submit-type="DELETE"
                                                                   href="{{action('Admin\Works\PhotographerWorkController@destroy',['id'=>$photographerWork->id])}}"
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
                                        {!! $photographerWorks->appends(array_merge($filter,$orderBy,['pageSize'=>$pageInfo['pageSize'],'photographer_id'=>$photographer['id']]))->links() !!}
                                    </div>
                                    <div class="pagination-info pull-right">
                                        <div>
                                            @php
                                                $pageUrl=action('Admin\Works\PhotographerWorkController@index',array_merge($filter,$orderBy,['photographer_id'=>$photographer['id']]));
                                                if(strpos($pageUrl,'?') !== false){
                                                        $pageUrl=$pageUrl.'&';
                                                }else{
                                                        $pageUrl=$pageUrl.'?';
                                                }
                                            @endphp
                                            <input type="text" class="form-control input-sm go-page" title="回车跳转"
                                                   value="{{$photographerWorks->currentPage()}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'page='+this.value+'&pageSize={{$pageInfo['pageSize']}}';}">
                                            / <strong>{{$photographerWorks->lastPage()}}</strong> 页，共
                                            <strong>{{$photographerWorks->total()}}</strong> 条数据，每页显示数量
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

            function photographerWorkCustomerIndustryInit() {
                var photographerWorkCustomerIndustries ={!! json_encode($photographerWorkCustomerIndustries) !!};
                var photographer_work_customer_industry_id ={!! intval($filter['photographer_work_customer_industry_id']) !!};
                var html1 = '<option value="">全部</option>';
                var html2 = [];
                var html2_0 = '<option value="">全部</option>';
                var html2_init = html2_0;
                var html2_select_index = 0;
                $(photographerWorkCustomerIndustries).each(function (k, v) {
                    var selected_html1 = '';
                    if (v.id == photographer_work_customer_industry_id) {
                        selected_html1 = 'selected';
                        html2_select_index = v.id;
                    }
                    html2[v.id] = html2_0;
                    $(v.children).each(function (_k, _v) {
                        var selected_html2 = '';
                        if (_v.id == photographer_work_customer_industry_id) {
                            selected_html1 = 'selected';
                            selected_html2 = 'selected';
                            html2_select_index = v.id;
                        }
                        html2[v.id] += '<option value="' + _v.id + '" ' + selected_html2 + '>' + _v.name + '</option>';
                    });
                    html1 += '<option value="' + v.id + '" ' + selected_html1 + '>' + v.name + '</option>';
                });
                html2_init = html2[html2_select_index];
                $('.photographer-work-customer-industry-box-1.photographer-work-customer-industry-level1').html(html1);
                $('.photographer-work-customer-industry-box-1.photographer-work-customer-industry-level2').html(html2_init);
                $(document).on('change', '.photographer-work-customer-industry-box-1', function () {
                    var className = '.photographer-work-customer-industry-box-1';
                    var level = $(this).attr('level');
                    var value = $(this).val();
                    if (level == 1) {
                        if (value === '') {
                            $(className + '.photographer-work-customer-industry-level2').html(html2_0);
                        } else {
                            $(className + '.photographer-work-customer-industry-level2').html(html2[value]);
                        }
                        $(className + '.hidden-value').val(value);
                    } else if (level == 2) {
                        if (value === '') {
                            $(className + '.hidden-value').val($(className + '.photographer-work-customer-industry-level1 option:checked').val());
                        } else {
                            $(className + '.hidden-value').val(value);
                        }
                    } else {
                        return false;
                    }
                });
            }

            photographerWorkCustomerIndustryInit();

            function photographerWorkCategoryInit() {
                var photographerWorkCategories ={!! json_encode($photographerWorkCategories) !!};
                var photographer_work_category_id ={!! intval($filter['photographer_work_category_id']) !!};
                var html1 = '<option value="">全部</option>';
                var html2 = [];
                var html2_0 = '<option value="">全部</option>';
                var html2_init = html2_0;
                var html2_select_index = 0;
                $(photographerWorkCategories).each(function (k, v) {
                    var selected_html1 = '';
                    if (v.id == photographer_work_category_id) {
                        selected_html1 = 'selected';
                        html2_select_index = v.id;
                    }
                    html2[v.id] = html2_0;
                    $(v.children).each(function (_k, _v) {
                        var selected_html2 = '';
                        if (_v.id == photographer_work_category_id) {
                            selected_html1 = 'selected';
                            selected_html2 = 'selected';
                            html2_select_index = v.id;
                        }
                        html2[v.id] += '<option value="' + _v.id + '" ' + selected_html2 + '>' + _v.name + '</option>';
                    });
                    html1 += '<option value="' + v.id + '" ' + selected_html1 + '>' + v.name + '</option>';
                });
                html2_init = html2[html2_select_index];
                $('.photographer-work-category-box-1.photographer-work-category-level1').html(html1);
                $('.photographer-work-category-box-1.photographer-work-category-level2').html(html2_init);
                $(document).on('change', '.photographer-work-category-box-1', function () {
                    var className = '.photographer-work-category-box-1';
                    var level = $(this).attr('level');
                    var value = $(this).val();
                    if (level == 1) {
                        if (value === '') {
                            $(className + '.photographer-work-category-level2').html(html2_0);
                        } else {
                            $(className + '.photographer-work-category-level2').html(html2[value]);
                        }
                        $(className + '.hidden-value').val(value);
                    } else if (level == 2) {
                        if (value === '') {
                            $(className + '.hidden-value').val($(className + '.photographer-work-category-level1 option:checked').val());
                        } else {
                            $(className + '.hidden-value').val(value);
                        }
                    } else {
                        return false;
                    }
                });
            }

            photographerWorkCategoryInit();
        });
    </script>
@endsection
