@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/select2/select2.min.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('/static/libs/select2/select2-bootstrap.min.css').'?'.$SFV}}">
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
                                        查找选择1
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <select class="js-select2 form-control select-linkage select2-hidden-accessible"
                                                name="demo_select2_1" aria-hidden="true">
                                            <option value="">请选择</option>
                                            <option value="1" @if($select21==1) selected @endif>查找选择1选项_1</option>
                                            <option value="2" @if($select21==2) selected @endif>查找选择1选项_2</option>
                                            <option value="3" @if($select21==3) selected @endif>查找选择1选项_3</option>
                                            <option value="4" @if($select21==4) selected @endif>查找选择1选项_4</option>
                                            <option value="5" @if($select21==5) selected @endif>查找选择1选项_5</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">查找选择1的提示信息</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-1 control-label form-option-line">
                                        查找选择2
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <select class="js-select2 form-control select-linkage select2-hidden-accessible"
                                                name="demo_select2_2" aria-hidden="true">
                                            <option value="">请选择</option>
                                            <option value="1" @if($select22==1) selected @endif>查找选择2选项_1</option>
                                            <option value="2" @if($select22==2) selected @endif>查找选择2选项_2</option>
                                            <option value="3" @if($select22==3) selected @endif>查找选择2选项_3</option>
                                            <option value="4" @if($select22==4) selected @endif>查找选择2选项_4</option>
                                            <option value="5" @if($select22==5) selected @endif>查找选择2选项_5</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">查找选择2的提示信息</div>
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
    <script src="{{asset('/static/libs/select2/select2.full.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/select2/i18n/zh-CN.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            App.initHelpers('select2');
        });
    </script>
    <script>
        $(function () {
            $(document).on('click', '#demo-submit', function () {
                $('#demo-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                var data = $('#demo-form').serialize();//表单数据
                Dolphin.loading('提交中...');
                $.ajax({
                    type: 'POST',
                    url: '{{action('Admin\System\DemoController@select2Save')}}',
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
