@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/webuploader/webuploader.css').'?'.$SFV}}">
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="demo-form">
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-1 control-label form-option-line">
                                        单文件上传1
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <div class="webuploader-box js-upload-file" upload-type="file">
                                            <input type="hidden" name="demo_webuploader_file_1"
                                                   value="{{$webuploader_file1}}">
                                            <div class="uploader-list">
                                                @if($webuploader_file1!=='')
                                                    <li class="list-group-item file-item upload-state-done"
                                                        style="word-wrap: break-word;">
                                                        <span class="pull-right file-state"></span>
                                                        <i class="fa fa-file"></i>{{$webuploader_file1}}&nbsp;&nbsp;
                                                        <span class="file-btns">
                                                            [<a href="javascript:void(0);"
                                                                class="remove-file">删除</a>]&nbsp;
                                                            [<a href="{{$webuploader_file1}}" target="_blank"
                                                                class="text-success">下载</a>]
                                                        </span>
                                                    </li>
                                                @endif
                                            </div>
                                            <div class="filePicker">上传单个文件</div>
                                            <span class="form-control-static form-option-line help-line form-option-webuploader-line">单文件上传1的提示信息</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-1 control-label form-option-line">
                                        多文件上传2
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <div class="webuploader-box js-upload-file" upload-type="file">
                                            <input type="hidden" name="demo_webuploader_file_2"
                                                   value="{{$webuploader_file2}}">
                                            <div class="uploader-list">
                                                @if($webuploader_file2!=='')
                                                    <li class="list-group-item file-item upload-state-done"
                                                        style="word-wrap: break-word;">
                                                        <span class="pull-right file-state"></span>
                                                        <i class="fa fa-file"></i>{{$webuploader_file2}}&nbsp;&nbsp;
                                                        <span class="file-btns">
                                                            [<a href="javascript:void(0);"
                                                                class="remove-file">删除</a>]&nbsp;
                                                            [<a href="{{$webuploader_file2}}" target="_blank"
                                                                class="text-success">下载</a>]
                                                        </span>
                                                    </li>
                                                @endif
                                            </div>
                                            <div class="filePicker">上传单个文件</div>
                                            <span class="form-control-static form-option-line help-line form-option-webuploader-line">多文件上传2的提示信息</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-username">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-minw btn-primary ajax-post" type="button"
                                                id="demo-submit">
                                            提交
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script src="{{asset('/static/libs/webuploader/webuploader.min.js').'?'.$SFV}}"></script>
    <script>
        var set_scene_uploader_file = ['demo_webuploader', 'demo_webuploader'];
    </script>
    <script src="{{asset('/static/admin/js/webuploader-file.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            $(document).on('click', '#demo-submit', function () {
                $('#demo-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                var data = $('#demo-form').serialize();//表单数据
                Dolphin.loading('提交中...');
                $.ajax({
                    type: 'POST',
                    url: '{{action('Admin\System\DemoController@webuploaderFileSave')}}',
                    dataType: 'JSON',
                    data: data,
                    success: function (response) {
                        if (response.status_code >= 200 && response.status_code < 300) {
                            Dolphin.rNotify(response.message, 'success');
                        } else {
                            Dolphin.loading('hide');
                            Dolphin.notify(response.message, 'danger');
                        }
                    },
                    error: function (xhr, status, error) {
                        var response = JSON.parse(xhr.responseText);
                        Dolphin.loading('hide');
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
            });
        });
    </script>
@endsection
