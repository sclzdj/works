@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
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
                    <h3 class="block-title">修改摄影师</h3>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-name">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        姓名
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="name" placeholder="请输入姓名"
                                               value="{{$photographer->name}}">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">自己控制好长度</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-avatar">
                                    <label class="col-md-1 control-label form-option-line">
                                        头像
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="avatar" readonly
                                               placeholder="请上传头像" value="{{$photographer->avatar}}">
                                        <input type="file" class="qiniu-file-upload hide" upload-max-size="5242880"
                                               mime-type='["image/png", "image/jpeg"]'>
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空
                                        </button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">最大5M，只支持mime类型为image/png、image/jpeg</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-bg_img">
                                    <label class="col-md-1 control-label form-option-line">
                                        背景图像
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="bg_img" readonly
                                               placeholder="请上传背景图片" value="{{$photographer->bg_img}}">
                                        <input type="file" class="qiniu-file-upload hide" upload-max-size="20971520"
                                               mime-type='["image/png", "image/jpeg"]'>
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空
                                        </button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">最大20M，只支持mime类型为image/png、image/jpeg</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-gender">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        性别
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="gender" value="0"
                                                   @if($photographer->gender==0) checked @endif>
                                            <span></span> 未知</label>
                                        </label>
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="gender" value="1"
                                                   @if($photographer->gender==1) checked @endif>
                                            <span></span> 男</label>
                                        </label>
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="gender" value="2"
                                                   @if($photographer->gender==2) checked @endif>
                                            <span></span> 女</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-area">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        地区
                                    </label>
                                    <div class="col-md-2 form-option-line">
                                        <select class="form-control area-select-box-1 area-select-box-province"
                                                level="1" name="province">
                                            <option value="">请选择省份</option>
                                            @foreach($provinces as $k=>$systemArea)
                                                <option value="{{$systemArea->id}}"
                                                        @if($photographer->province==$systemArea->id) selected @endif>
                                                    {{$systemArea->short_name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-option-line">
                                        <select class="form-control area-select-box-1 area-select-box-city" level="2"
                                                name="city">
                                            <option value="">请选择城市</option>
                                            @foreach($cities as $k=>$systemArea)
                                                <option value="{{$systemArea->id}}"
                                                        @if($photographer->city==$systemArea->id) selected @endif>
                                                    {{$systemArea->short_name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-option-line">
                                        <select class="form-control area-select-box-1 area-select-box-area" level="3"
                                                name="area">
                                            <option value="">请选择地方</option>
                                            @foreach($areas as $k=>$systemArea)
                                                <option value="{{$systemArea->id}}"
                                                        @if($photographer->area==$systemArea->id) selected @endif>
                                                    {{$systemArea->short_name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">每一项都必须选择</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-photographer_rank_id">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        头衔
                                    </label>
                                    <input type="hidden" name="photographer_rank_id"
                                           value=""
                                           class="photographer-rank-box-1 hidden-value">
                                    <div class="col-md-3 form-option-line">
                                        <select class="form-control photographer-rank-box-1 photographer-rank-level1"
                                                level="1">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-option-line">
                                        <select class="form-control photographer-rank-box-1 photographer-rank-level2"
                                                level="2">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">每一项都必须选择</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-wechat">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        微信号
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="wechat" placeholder="请输入微信号"
                                               value="{{$photographer->wechat}}">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">自己控制好长度</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-mobile">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        手机号
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="mobile" placeholder="请输入手机号"
                                               value="{{$photographer->mobile}}">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">11位正确格式的手机号</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-minw btn-primary ajax-post" type="button"
                                                id="create-submit">
                                            提交
                                        </button>
                                        <button class="btn btn-default" type="button"
                                                onclick="javascript:history.back(-1);return false;">
                                            返回
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
    <script src="{{asset('/static/admin/js/change-node.js').'?'.$SFV}}"></script>
    <script src="https://unpkg.com/qiniu-js@2.5.4/dist/qiniu.min.js"></script>
    @php
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 用于签名的公钥和私钥
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        // 初始化签权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        // 生成上传Token
        $upToken = $auth->uploadToken($bucket);
        $qiniu_config=compact('upToken', 'domain');
    @endphp
    <script>
        var qiniu_config = {!! json_encode($qiniu_config) !!};
    </script>
    <script src="{{asset('/static/admin/js/qiniu-upload.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            $(document).on('click', '#create-submit', function () {
                $('#create-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                var data = $('#create-form').serialize();//表单数据
                Dolphin.loading('提交中...');
                $.ajax({
                    type: 'PUT',
                    url: '{{action('Admin\Works\PhotographerController@update',['id'=>$photographer->id])}}',
                    dataType: 'JSON',
                    data: data,
                    success: function (response) {
                        if (response.status_code >= 200 && response.status_code < 300) {
                            Dolphin.jNotify(response.message, 'success', response.data.url);
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
                var city_html = '<option value="">请选择城市</option>';
                var area_html = '<option value="">请选择地方</option>';
                if (level == 1) {
                    if (value === '') {
                        $(className + '.area-select-box-city').html(city_html);
                        $(className + '.area-select-box-area').html(area_html);
                    } else {
                        var url = '/api/getCitys';
                        var data = {province_id: value};
                        run(url, data, function (data) {
                            $(data.data).each(function (k, v) {
                                city_html += '<option value="' + v.id + '">' + v.short_name + '</option>';
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
                                area_html += '<option value="' + v.id + '">' + v.short_name + '</option>';
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
                var photographer_rank_id ={!! intval($photographer['photographer_rank_id']) !!};
                var html1 = '<option value="">请选择</option>';
                var html2 = [];
                var html2_0 = '<option value="">请选择</option>';
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
                            $('.photographer-rank-box-1.hidden-value').val(photographer_rank_id);
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
                        $(className + '.hidden-value').val('');
                    } else if (level == 2) {
                        $(className + '.hidden-value').val(value);
                    } else {
                        return false;
                    }
                });
            }

            photographerRankInit();
        });
    </script>
@endsection
