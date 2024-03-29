@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/viewer/viewer.min.css').'?'.$SFV}}">
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
                    <h3 class="block-title">图库</h3>
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div style="margin: 20px 2%;margin-top: 0;">
                            <div class="row">
                                @foreach($photographerWorkSources as $photographerWorkSource)
                                    <div class="card gallery-list" style="float: left;margin: 5px 20px;">
                                        <div class="row">
                                            <div class="col-12">
                                                <img class="image img-thumbnail"
                                                     data-original="{{$photographerWorkSource->thumb_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     src="{{$photographerWorkSource->thumb_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     alt="{{$photographerWorkSource->id}}"
                                                     style="max-width: 200px;height:200px;border-bottom-left-radius: 0;border-bottom-right-radius: 0;">
                                                <div data-toggle="tooltip" data-original-title="客户名称：{{$photographerWorkSource->customer_name}}" class="border text-center" style="font-size:12px;padding:0 5px;width: 200px;height:24px;line-height:24px;border-radius: 0;border-bottom: none;border-top: none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                    {{$photographerWorkSource->customer_name}}
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <img data-original="{{$photographerWorkSource->url}}"
                                                     src="{{$photographerWorkSource->url}}"
                                                     alt="{{$photographerWorkSource->key}}"
                                                     style="display: none;">
                                                <a class="btn btn-default" href="javascript:;" onclick="$(this).parent().find('img').click()"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;border-bottom: none;">原图</a>
                                            </div>
                                            <div class="col-12">
                                                <img data-original="{{$photographerWorkSource->deal_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     src="{{$photographerWorkSource->deal_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     alt="{{$photographerWorkSource->deal_key}}"
                                                     style="display: none;">
                                                <a class="btn btn-default" href="javascript:;" onclick="$(this).parent().find('img').click()"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;border-bottom: none;">1200图</a>
                                            </div>
                                            <div class="col-12">
                                                <img data-original="{{$photographerWorkSource->rich_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     src="{{$photographerWorkSource->rich_url?:asset('/static/admin/img/in-generation.png'.'?'.$SFV)}}"
                                                     alt="{{$photographerWorkSource->rich_key}}"
                                                     style="display: none;">
                                                <a class="btn btn-default" href="javascript:;" onclick="$(this).parent().find('img').click()"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;border-bottom: none;">水印图</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="data-table-toolbar">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pagination-info pull-left">
                                        {!! $photographerWorkSources->appends(array_merge(['id'=>$photographer->id,'pageSize'=>$pageInfo['pageSize']]))->links() !!}
                                    </div>
                                    <div class="pagination-info pull-right">
                                        <div>
                                            @php
                                                $pageUrl=action('Admin\Works\PhotographerController@gallery',['id'=>$photographer->id]);
                                                if(strpos($pageUrl,'?') !== false){
                                                        $pageUrl=$pageUrl.'&';
                                                }else{
                                                        $pageUrl=$pageUrl.'?';
                                                }
                                            @endphp
                                            <input type="text" class="form-control input-sm go-page" title="回车跳转"
                                                   value="{{$photographerWorkSources->currentPage()}}"
                                                   onkeyup="if(event.keyCode==13){location.href='{{$pageUrl}}'+'page='+this.value+'&pageSize={{$pageInfo['pageSize']}}';}">
                                            / <strong>{{$photographerWorkSources->lastPage()}}</strong> 页，共
                                            <strong>{{$photographerWorkSources->total()}}</strong> 条数据，每页显示数量
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
    <script>
        $(function () {
            Dolphin.viewer();
        });
    </script>
@endsection
