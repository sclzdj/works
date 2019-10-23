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
                    <h3 class="block-title">摄影师图库</h3>
                </div>
                <div class="block-content tab-content">
                    <div class="tab-pane active">
                        <div style="margin: 20px 2%;margin-top: 0;">
                            <div class="row">
                                @foreach($photographerWorkSources as $photographerWorkSource)
                                    <div class="card gallery-list" style="float: left;margin: 0 20px;">
                                        <div class="row" style="padding:10px 3%;">
                                            <div class="col-12">
                                                <img class="image img-thumbnail"
                                                     data-original="{{$photographerWorkSource->deal_url}}"
                                                     src="{{$photographerWorkSource->deal_url}}"
                                                     alt="{{$photographerWorkSource->deal_key}}"
                                                     style="max-width: 200px;width: 200px;height:200px;border-bottom-left-radius: 0;border-bottom-right-radius: 0;border-bottom: none;">
                                            </div>
                                            <div class="col-12">
                                                <a class="btn btn-default" href="{{$photographerWorkSource->url}}"
                                                   download="{{$photographerWorkSource->key}}" target="_blank"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;border-bottom: none;">原图</a>
                                            </div>
                                            <div class="col-12">
                                                <a class="btn btn-default"
                                                   href="{{$photographerWorkSource->deal_url}}"
                                                   download="{{$photographerWorkSource->deal_key}}" target="_blank"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;border-bottom: none;">处理图</a>
                                            </div>
                                            <div class="col-12">
                                                <a class="btn btn-default"
                                                   href="{{$photographerWorkSource->rich_url}}"
                                                   download="{{$photographerWorkSource->rich_key}}" target="_blank"
                                                   style="width: 200px;border-top-left-radius: 0;border-top-right-radius: 0;">水印图</a>
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
