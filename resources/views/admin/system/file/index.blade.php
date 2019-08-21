@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/viewer/viewer.min.css').'?'.$SFV}}">
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
                    <h3 class="block-title">文件管理</h3>
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div class="row data-table-toolbar">
                            <div class="col-sm-12">
                                <div class="pull-left toolbar-btn-action">
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\IndexController@config'))
                                        <a class="btn btn-primary btn-table-top"
                                           href="{{action('Admin\System\IndexController@config',['type'=>'upload'])}}"><i
                                                    class="fa fa-fw fa-gear"></i>上传配置</a>
                                    @endif
                                    @if(\App\Servers\PermissionServer::allowAction('Admin\System\FileController@destroy'))
                                        <a class="btn btn-danger btn-table-top ids-submit" submit-type="DELETE"
                                           href="{{action('Admin\System\FileController@destroy',['id'=>0])}}"
                                           confirm="<div class='text-center'>删除操作会将其关联数据<b class='text-danger'>全部删除，且不可恢复</b>；确定要删除吗？</div>"><i
                                                    class="fa fa-times-circle-o"></i> 删除</a>
                                    @endif
                                </div>
                                <form action="{{action('Admin\System\FileController@index')}}" method="get">
                                    <input type="hidden" name="order_field" value="{{$orderBy['order_field']}}">
                                    <input type="hidden" name="order_type" value="{{$orderBy['order_type']}}">
                                    <div class="pull-right text-right">
                                        <div class="search-bar search-bar-300" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">链接或名称</div>
                                                <input type="text" class="form-control" value="{{$filter['url']}}"
                                                       name="url" placeholder="请输入链接或名称">
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-130" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">类型</div>
                                                <select class="form-control" name="mimeType">
                                                    <option value="">全部</option>
                                                    <option value="image/"
                                                            @if($filter['mimeType']==='image/') selected @endif>图片
                                                    </option>
                                                    <option value="audio/"
                                                            @if($filter['mimeType']==='audio/') selected @endif>音频
                                                    </option>
                                                    <option value="video/"
                                                            @if($filter['mimeType']==='video/') selected @endif>视频
                                                    </option>
                                                    <option value="text/"
                                                            @if($filter['mimeType']==='text/') selected @endif>文本
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-150" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">驱动</div>
                                                <select class="form-control" name="driver">
                                                    <option value="">全部</option>
                                                    <option value="local"
                                                            @if($filter['driver']==='local') selected @endif>本地
                                                    </option>
                                                    <option value="oss" @if($filter['driver']==='oss') selected @endif>
                                                        阿里云
                                                    </option>
                                                    <option value="2" @if($filter['driver']==='qiniu') selected @endif>
                                                        七牛云
                                                    </option>
                                                    <option value="s3" @if($filter['driver']==='s3') selected @endif>
                                                        S3
                                                    </option>
                                                    <option value="ftp" @if($filter['driver']==='ftp') selected @endif>
                                                        FTP
                                                    </option>
                                                    <option value="sftp"
                                                            @if($filter['driver']==='sftp') selected @endif>SFTP
                                                    </option>
                                                    <option value="rackspace"
                                                            @if($filter['driver']==='rackspace') selected @endif>
                                                        Rackspace
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-130" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">磁盘</div>
                                                <select class="form-control" name="disk">
                                                    <option value="">全部</option>
                                                    <option value="local"
                                                            @if($filter['disk']==='local') selected @endif>local
                                                    </option>
                                                    <option value="s3" @if($filter['disk']==='s3') selected @endif>s3
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-130" style="display: inline-block">
                                            <div class="input-group">
                                                <div class="input-group-addon">场景</div>
                                                <select class="form-control" name="scene">
                                                    <option value="">全部</option>
                                                    @foreach($scenes as $scene)
                                                        <option value="{{$scene['scene']}}"
                                                                @if($filter['scene']===$scene['scene']) selected @endif>{{$scene['scene']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="search-bar search-bar-300" style="display: inline-block">
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
                                                <a href="{{action('Admin\System\FileController@index',array_merge($orderBy,['pageSize'=>$pageInfo['pageSize']]))}}"
                                                   class="btn btn-default" style="margin-left: 5px;">清空</a>
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
                                    <table class="table table-builder table-hover table-bordered table-striped js-table-checkable"
                                           style="width: 1571px;">
                                        <colgroup>
                                            <col width="50">
                                            <col class="" width="50">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                            <col class="" width="100">
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <th class="text-center">
                                                <label class="css-input css-checkbox css-checkbox-primary remove-margin-t remove-margin-b">
                                                    <input type="checkbox" id="check-all"><span></span>
                                                </label>
                                            </th>
                                            <th class="">
                                                ID
                                                @if($orderBy['order_field']=='id')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'id','order_type'=>'desc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击降序"
                                                                 alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'id','order_type'=>'asc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击升序"
                                                                 alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'id'],$pageInfo))}}"
                                                             data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                文件<span></span>
                                            </th>
                                            <th class="">
                                                类型<span></span>
                                            </th>
                                            <th class="">
                                                后缀名<span></span>
                                            </th>
                                            <th class="">
                                                大小
                                                @if($orderBy['order_field']=='size')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'size','order_type'=>'desc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击降序"
                                                                 alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'size','order_type'=>'asc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击升序"
                                                                 alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'size'],$pageInfo))}}"
                                                             data-toggle="tooltip" data-original-title="点击排序" alt="未排序">
                                                            <i class="fa fa-sort text-muted"></i>
                                                        </a></span>
                                                @endif
                                            </th>
                                            <th class="">
                                                驱动<span></span>
                                            </th>
                                            <th class="">
                                                磁盘<span></span>
                                            </th>
                                            <th class="">
                                                场景<span></span>
                                            </th>
                                            <th class="">
                                                创建时间
                                                @if($orderBy['order_field']=='created_at')
                                                    @if($orderBy['order_type']=='asc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'desc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击降序"
                                                                 alt="已升序">
                                                            <i class="fa fa-caret-up"></i>
                                                        </a></span>
                                                    @elseif($orderBy['order_type']=='desc')
                                                        <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'created_at','order_type'=>'asc'],$pageInfo))}}"
                                                                 data-toggle="tooltip" data-original-title="点击升序"
                                                                 alt="已降序">
                                                            <i class="fa fa-caret-down"></i>
                                                        </a></span>
                                                    @endif
                                                @else
                                                    <span><a href="{{action('Admin\System\FileController@index',array_merge($filter,['order_field'=>'created_at'],$pageInfo))}}"
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
                                    <table class="table table-builder table-hover table-bordered table-striped js-table-checkable-target"
                                           id="builder-table-main">
                                        <colgroup>
                                            <col width="50">
                                            <col width="50" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                            <col width="100" class="">
                                        </colgroup>
                                        <tbody>
                                        @forelse ($systemFiles as $key=>$systemFile)
                                            <tr class="">
                                                <td class="text-center">
                                                    <div class="table-cell">
                                                        <label class="css-input css-checkbox css-checkbox-primary">
                                                            <input class="ids" type="checkbox" name="ids[]"
                                                                   value="{{$systemFile->id}}"><span></span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$systemFile->id}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        @if($systemFile->url!=='')
                                                            @if(strpos($systemFile->mimeType,'image/')!==false)
                                                                <div class="js-gallery">
                                                                    @if($systemFile->driver=='local' && ($systemFile->upload_type=='image' || $systemFile->upload_type=='images') && !in_array($systemFile->scene,config('custom.upload_image_special_scenes')))
                                                                        <img title="{{$systemFile->name}}"
                                                                             class="image"
                                                                             data-original="{{$systemFile->url.'&type=1'}}"
                                                                             src="{{$systemFile->url.'&type=2'}}">
                                                                    @else
                                                                        <img title="{{$systemFile->name}}" class="image"
                                                                             data-original="{{$systemFile->url}}"
                                                                             src="{{$systemFile->url}}">
                                                                    @endif
                                                                </div>
                                                            @else
                                                                @if(file_exists('./static/admin/img/files/'.$systemFile->extension.'.png'))
                                                                    <img title="{{$systemFile->name}}" class="image"
                                                                         src="{{asset('/static/admin/img/files/'.$systemFile->extension.'.png').'?'.$SFV}}">
                                                                @else
                                                                    <img title="{{$systemFile->name}}" class="image"
                                                                         src="{{asset('/static/admin/img/files/file.png').'?'.$SFV}}">
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        @if(strpos($systemFile->mimeType,'image/')!==false)
                                                            图片
                                                        @elseif(strpos($systemFile->mimeType,'audio/')!==false)
                                                            音频
                                                        @elseif(strpos($systemFile->mimeType,'video/')!==false)
                                                            视频
                                                        @elseif(strpos($systemFile->mimeType,'text/')!==false)
                                                            文本
                                                        @else
                                                            文件
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$systemFile->extension}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        @if($systemFile->size<1024)
                                                            {{$systemFile->size}} <i class="text-muted"> B</i>
                                                        @elseif($systemFile->size<1024*1024)
                                                            {{number_format($systemFile->size/1024,2,'.','')}}<i
                                                                    class="text-muted">KB</i>
                                                        @elseif($systemFile->size<1024*1024*1024)
                                                            {{number_format($systemFile->size/1024/1024,2,'.','')}}<i
                                                                    class="text-muted"> MB</i>
                                                        @elseif($systemFile->size>=1024*1024*1024*1024)
                                                            {{number_format($systemFile->size/1024/1024,2,'.','')}}<i
                                                                    class="text-muted"> TB</i>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        @if($systemFile->driver=='local')
                                                            本地
                                                        @elseif($systemFile->driver=='oss')
                                                            阿里云
                                                        @elseif($systemFile->driver=='qiniu')
                                                            七牛云
                                                        @elseif($systemFile->driver=='s3')
                                                            S3
                                                        @elseif($systemFile->driver=='ftp')
                                                            FTP
                                                        @elseif($systemFile->driver=='sftp')
                                                            SFTP
                                                        @elseif($systemFile->driver=='rackspace')
                                                            Rackspace
                                                        @else
                                                            其它
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        @if($systemFile->disk=='local')
                                                            local
                                                        @elseif($systemFile->disk=='s3')
                                                            s3
                                                        @else
                                                            其它
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$systemFile->scene}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        {{$systemFile->created_at}}
                                                    </div>
                                                </td>
                                                <td class=" ">
                                                    <div class="table-cell">
                                                        <div class="btn-group">
                                                            @if($systemFile->original_url!=='')
                                                                <a class="btn btn-xs btn-default" target="_blank"
                                                                   href="{{$systemFile->original_url}}" download="{{$systemFile->name}}">下载</a>
                                                            @endif
                                                            @if(\App\Servers\PermissionServer::allowAction('Admin\System\FileController@destroy'))
                                                                <a class="btn btn-xs btn-default id-submit"
                                                                   submit-type="DELETE"
                                                                   href="{{action('Admin\System\FileController@destroy',['id'=>$systemFile->id])}}"
                                                                   confirm="<div class='text-center'><b class='text-danger'>删除数据时若其有关联数据将不会进行删除</b>；是否继续？</div>">删除</a>
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
                                        {!! $systemFiles->appends(array_merge($filter,$orderBy,['pageSize'=>$pageInfo['pageSize']]))->links() !!}
                                    </div>
                                    <div class="pagination-info pull-right">
                                        <div>
                                            @php
                                                $pageUrl=action('Admin\System\FileController@index',array_merge($filter,$orderBy));
                                                if(strpos($pageUrl,'?') !== false){
                                                        $pageUrl=$pageUrl.'&';
                                                }else{
                                                        $pageUrl=$pageUrl.'?';
                                                }
                                            @endphp
                                            <input type="text" class="form-control input-sm go-page" title="回车跳转"
                                                   value="{{$systemFiles->currentPage()}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'page='+this.value+'&pageSize={{$pageInfo['pageSize']}}';}">
                                            / <strong>{{$systemFiles->lastPage()}}</strong> 页，共
                                            <strong>{{$systemFiles->total()}}</strong> 条数据，每页显示数量
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
    <script src="{{asset('/static/libs/viewer/viewer.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/bootstrap3-editable/js/bootstrap-editable.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/table-init.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/table-submit.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/bootstrap-datepicker/bootstrap-datepicker.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            App.initHelpers(["datepicker"]);
        });
    </script>
@endsection
