@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    <link rel="stylesheet" href="{{asset('/static/libs/select2/select2.min.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('/static/libs/select2/select2-bootstrap.min.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('/static/libs/fontawesome-iconpicker/fontawesome-iconpicker.css').'?'.$SFV}}">
@endsection
@section('content')
    <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <p><strong><i class="fa fa-fw fa-hand-o-right"></i> 说明：</strong>1级节点服务顶部导航模块，2级节点服务左侧一级菜单，3级节点服务左侧二级菜单，4级节点及之后均服务于页面操作方法</p>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <div class="block-header bg-gray-lighter">
                    <ul class="block-options">
                        <li>
                            <button type="button" class="page-reload"><i class="si si-refresh"></i></button>
                        </li>
                        <li>
                            <button type="button" data-toggle="block-option" data-action="fullscreen_toggle"><i class="si si-size-fullscreen"></i></button>
                        </li>
                    </ul>
                    <h3 class="block-title">添加节点</h3>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-pid">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        所属父级
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <select class="js-select2 form-control select-linkage select2-hidden-accessible" name="pid" aria-hidden="true">
                                            <option value="">顶级节点（模块）</option>
                                            @foreach($treeNodes as $node)
                                                <option value="{{$node['id']}}" @if($node['id']==$pid) selected @endif>{!! $node['_html'] !!}&nbsp;├─&nbsp;{{$node['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">节点过多时请输入查找</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-name">
                                    <label class="col-md-1 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        名称
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" type="text" name="name" value="" placeholder="请输入名称">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">2-10个字符</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-action">
                                    <label class="col-md-1 control-label form-option-line">
                                        动作方法
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" type="text" name="action" value="" placeholder="请输入动作方法">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">3-100个字符；二、三级节点一定要是<span class="text-danger">有效</span>并且<span class="text-danger">不需必带参数</span>的的动作方法；否则将报错</div>
                                    </div>
                                    <div class="col-md-11 col-md-offset-1 form-control-static form-option-line">
                                        <div class="help-block help-block-line">格式示例：<span class="text-danger">Admin\Module\CtrlController@action</span>；模块无需填写，二级节点若有子节点可省略，三级及之后的节点必须填写</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-action">
                                    <label class="col-md-1 control-label form-option-line">
                                        图标
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <div class="input-group js-icon-picke">
                                            <input autocomplete="off" name="icon" placeholder="请选择图标" class="form-control icp icp-auto" type="text"/>
                                            <span class="input-group-addon"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">一般前三级节点都要选择，四级节点不用选择</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-status">
                                    <label class="col-md-1 control-label form-option-line">
                                        状态
                                    </label>
                                    <div class="col-md-11 form-option-line">
                                        <label class="css-input switch switch-sm switch-primary switch-rounded " title="启用/禁用">
                                            <input type="checkbox" name="status" value="1" checked><span></span>
                                        </label>
                                        <span class="form-control-static form-option-line help-line">关闭后节点会被禁用</span>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-sort">
                                    <label class="col-md-1 control-label form-option-line">
                                        排序
                                    </label>
                                    <div class="col-md-6 form-option-line">
                                        <input class="form-control" type="text" name="sort" value="100" placeholder="请输入排序">
                                    </div>
                                    <div class="col-md-5 form-control-static form-option-line">
                                        <div class="help-block help-block-line">只能是数值</div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="create-name">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-minw btn-primary ajax-post" type="button" id="create-submit">
                                            提交
                                        </button>
                                        <button class="btn btn-default" type="button" onclick="javascript:history.back(-1);return false;">
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
    <script src="{{asset('/static/libs/select2/select2.full.min.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/select2/i18n/zh-CN.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/libs/fontawesome-iconpicker/fontawesome-iconpicker.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/iconpicker.js').'?'.$SFV}}"></script>
    <script>
        $(function () {
            App.initHelpers(['select2']);

            $(document).on('click', '#create-submit', function () {
                $('#create-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                var data = $('#create-form').serialize();//表单数据
                Dolphin.loading('提交中...');
                $.ajax({
                    type: 'POST',
                    url: '{{action('Admin\System\NodeController@store')}}',
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
        });
    </script>
@endsection
