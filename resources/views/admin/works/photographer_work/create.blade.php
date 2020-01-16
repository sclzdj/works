@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/jquery-tags-input/jquery.tagsinput.css').'?'.$SFV}}">
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
                    <h3 class="block-title">添加作品集</h3>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-1 control-label form-option-line">
                                        摄影师
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <div class="form-control-static">{{$photographer->name}}</div>
                                        <input type="hidden" name="photographer_id" value="{{$photographer->id}}">
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-customer_name">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        客户名称
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="customer_name" placeholder="请输入客户名称"
                                               value="">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">自己控制好长度</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-photographer_work_customer_industry_id">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        客户行业
                                    </label>
                                    <input type="hidden" name="photographer_work_customer_industry_id"
                                           value=""
                                           class="photographer-work-customer-industry-box-1 hidden-value">
                                    <div class="col-md-3 form-option-line">
                                        <select
                                            class="form-control photographer-work-customer-industry-box-1 photographer-work-customer-industry-level1"
                                            level="1">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-option-line">
                                        <select
                                            class="form-control photographer-work-customer-industry-box-1 photographer-work-customer-industry-level2"
                                            level="2">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">每一项都必须选择</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-project_amount">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        项目金额
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="project_amount" placeholder="请输入项目金额"
                                               value="">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能是数字</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-hide_project_amount">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        项目金额状态
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_project_amount" value="0" checked>
                                            <span></span> 显示
                                        </label>
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_project_amount" value="1">
                                            <span></span> 隐藏
                                        </label>
                                        <span class="form-control-static form-option-line help-line">隐藏后前台将不再展示</span>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-sheets_number">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        成片张数
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="sheets_number" placeholder="请输入成片张数"
                                               value="">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能是数字</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-hide_sheets_number">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        成片张数状态
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_sheets_number" value="0" checked>
                                            <span></span> 显示
                                        </label>
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_sheets_number" value="1">
                                            <span></span> 隐藏
                                        </label>
                                        <span class="form-control-static form-option-line help-line">隐藏后前台将不再展示</span>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-shooting_duration">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        拍摄时长
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" name="shooting_duration" placeholder="请输入拍摄时长"
                                               value="">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能是数字</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-hide_shooting_duration">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        拍摄时长状态
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_shooting_duration" value="0" checked>
                                            <span></span> 显示
                                        </label>
                                        <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                            <input type="radio" name="hide_shooting_duration" value="1">
                                            <span></span> 隐藏
                                        </label>
                                        <span class="form-control-static form-option-line help-line">隐藏后前台将不再展示</span>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-photographer_work_category_id">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        领域
                                    </label>
                                    <input type="hidden" name="photographer_work_category_id"
                                           value=""
                                           class="photographer-work-category-box-1 hidden-value">
                                    <div class="col-md-3 form-option-line">
                                        <select
                                            class="form-control photographer-work-category-box-1 photographer-work-category-level1"
                                            level="1">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-option-line">
                                        <select
                                            class="form-control photographer-work-category-box-1 photographer-work-category-level2"
                                            level="2">
                                            <option value="">请选择</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">每一项都必须选择</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                     id="create-tags">
                                    <label class="col-md-1 control-label form-option-line">
                                        标签
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control tags-input" type="text" name="tags" value=""
                                               placeholder="请输入标签">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">敲回车或英文逗号输入下一个标签</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[0]">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        资源1
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[1]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源2
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[2]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源3
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[3]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源4
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[4]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源5
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[5]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源6
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[6]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源7
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空
                                        </button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[7]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源8
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
                                    </div>
                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sources[8]">
                                    <label class="col-md-1 control-label form-option-line">
                                        资源9
                                    </label>
                                    <div class="col-md-5 form-option-line">
                                        <input class="form-control qiniu-file-upload-trigger" name="sources[]" readonly
                                               placeholder="请上传图片或视频">
                                        <input type="file" class="qiniu-file-upload hide" value-type="key">
                                    </div>
                                    <div class="col-md-1 form-option-line">
                                        <button type="button" class="btn btn-default qiniu-file-upload-clear">清空</button>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能上传图片(mime类型必须为image/png、image/jpeg，否则小程序海报和水印生成会报错)或视频</div>
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
    <script src="{{asset('/static/libs/jquery-tags-input/jquery.tagsinput.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/tags-input.js').'?'.$SFV}}"></script>
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
                    type: 'POST',
                    url: '{{action('Admin\Works\PhotographerWorkController@store')}}',
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

            function photographerWorkCustomerIndustryInit() {
                var photographerWorkCustomerIndustries ={!! json_encode($photographerWorkCustomerIndustries) !!};
                var photographer_work_customer_industry_id = 0;
                var html1 = '<option value="">请选择</option>';
                var html2 = [];
                var html2_0 = '<option value="">请选择</option>';
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
                        $(className + '.hidden-value').val('');
                    } else if (level == 2) {
                        $(className + '.hidden-value').val(value);
                    } else {
                        return false;
                    }
                });
            }

            photographerWorkCustomerIndustryInit();

            function photographerWorkCategoryInit() {
                var photographerWorkCategories ={!! json_encode($photographerWorkCategories) !!};
                var photographer_work_category_id = 0;
                var html1 = '<option value="">请选择</option>';
                var html2 = [];
                var html2_0 = '<option value="">请选择</option>';
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
                        $(className + '.hidden-value').val('');
                    } else if (level == 2) {
                        $(className + '.hidden-value').val(value);
                    } else {
                        return false;
                    }
                });
            }

            photographerWorkCategoryInit();
        });
    </script>
@endsection
