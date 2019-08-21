@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/viewer/viewer.min.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('/static/libs/webuploader/webuploader.css').'?'.$SFV}}">
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
                    <h3 class="block-title">资料设置</h3>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-password">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        密码
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" type="password" name="password" value=""
                                               placeholder="请输入密码">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">留空则不修改，5-18个字符，不包含中文，只支持部分特殊字符</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-nickname">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        昵称
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" type="text" name="nickname"
                                               value="{{$systemUser->nickname}}" placeholder="请输入昵称">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">2-10个字符</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-avatar">
                                    <label class="col-md-1 control-label form-option-line">
                                        头像
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <div class="webuploader-box js-upload-image" upload-type="image">
                                            <input type="hidden" name="avatar" value="{{$systemUser->avatar}}">
                                            <div class="uploader-list">
                                                @if($systemUser->avatar!=='')
                                                    <div class="file-item js-gallery thumbnail upload-state-done">
                                                        <img src="{{$systemUser->avatar}}"
                                                             data-original="{{$systemUser->avatar}}" width="100"
                                                             height="100">
                                                        <div class="info">{{$systemUser->avatar}}</div>
                                                        <i class="fa fa-times-circle remove-picture"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="filePicker">上传单张图片</div>
                                            <span class="form-control-static form-option-line help-line form-option-webuploader-line">若不上传，将使用系统默认头像</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-username">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-minw btn-primary ajax-post" type="button"
                                                id="create-submit">
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
    <script src="{{asset('/static/libs/viewer/viewer.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/webuploader/webuploader.min.js').'?'.$SFV}}"></script>
    <script>
        //设置图片上传的场景
        var set_scene_uploader_image = ['set_admin_avatar'];
    </script>
    <script src="{{asset('/static/admin/js/webuploader-image.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            $(document).on('click', '#create-submit', function () {
                $('#create-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                var data = $('#create-form').serialize();//表单数据
                Dolphin.loading('提交中...');
                $.ajax({
                    type: 'PUT',
                    url: '{{action('Admin\System\IndexController@setInfo')}}',
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
