@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')
@section('pre_css')
    @if(in_array('select2',$genres))
        <link rel="stylesheet" href="{{asset('/static/libs/select2/select2.min.css').'?'.$SFV}}">
        <link rel="stylesheet" href="{{asset('/static/libs/select2/select2-bootstrap.min.css').'?'.$SFV}}">
    @endif
    @if(in_array('tags',$genres))
        <link rel="stylesheet" href="{{asset('/static/libs/jquery-tags-input/jquery.tagsinput.css').'?'.$SFV}}">
    @endif
    @if(in_array('image',$genres) || in_array('images',$genres) || in_array('file',$genres) || in_array('files',$genres))
        @if(in_array('images',$genres) || in_array('files',$genres))
            <link rel="stylesheet" href="{{asset('/static/libs/jquery-nestable/jquery.nestable.css').'?'.$SFV}}">
        @endif
        <link rel="stylesheet" href="{{asset('/static/libs/webuploader/webuploader.css').'?'.$SFV}}">
        @if(in_array('image',$genres) || in_array('images',$genres))
            <link rel="stylesheet" href="{{asset('/static/libs/viewer/viewer.min.css').'?'.$SFV}}">
        @endif
    @endif
    @if(in_array('date',$genres))
        <link rel="stylesheet"
              href="{{asset('/static/libs/bootstrap-datepicker/bootstrap-datepicker3.min.css').'?'.$SFV}}">
    @endif
    @if(in_array('datetime',$genres))
        <link rel="stylesheet"
              href="{{asset('/static/libs/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css').'?'.$SFV}}">
    @endif
    @if(in_array('ueditor',$genres))
    @endif
    @if(in_array('icon',$genres))
        <link rel="stylesheet"
              href="{{asset('/static/libs/fontawesome-iconpicker/fontawesome-iconpicker.css').'?'.$SFV}}">
    @endif
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="block">
                <ul class="nav nav-tabs">
                    @foreach($types as $k=>$t)
                        <li class="@if($type==$k) active @endif">
                            <a href="{{action('Admin\System\IndexController@config',['type'=>$k])}}">{{$t}}</a>
                        </li>
                    @endforeach
                    <li class="pull-right">
                        <ul class="block-options push-10-t push-10-r">
                            <li>
                                <button type="button" class="page-reload"><i class="si si-refresh"></i></button>
                            </li>
                            <li>
                                <button type="button" data-toggle="block-option" data-action="fullscreen_toggle"><i
                                            class="si si-size-fullscreen"></i></button>
                            </li>
                        </ul>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="config-form">
                                @foreach($systemConfigs as $systemConfig)
                                    <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12"
                                         id="config-{{$systemConfig['name']}}">
                                        <label class="col-md-1 control-label form-option-line">
                                            @if($systemConfig['required'])
                                                <span class="form-option-require"></span>
                                            @endif
                                            {{$systemConfig['title']}}
                                        </label>
                                        @if($systemConfig['genre']=='static')
                                            <div class="col-md-6 form-option-line">
                                                <input type="hidden" name="{{$systemConfig['name']}}"
                                                       value="{{$systemConfig['value']}}">
                                                <div class="form-control-static">{{$systemConfig['value']}}</div>
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='text')
                                            <div class="col-md-6 form-option-line">
                                                <input class="form-control" type="text" name="{{$systemConfig['name']}}"
                                                       value="{{$systemConfig['value']}}"
                                                       placeholder="请输入{{$systemConfig['title']}}">
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='textarea')
                                            <div class="col-md-6 form-option-line">
                                                <textarea class="form-control" rows="7" name="{{$systemConfig['name']}}"
                                                          placeholder="请输入{{$systemConfig['title']}}"
                                                          name="{{$systemConfig['name']}}">{{$systemConfig['value']}}</textarea>
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='radio')
                                            <div class="col-md-11 form-option-line">
                                                @foreach(json_decode($systemConfig['options'],true) as $k=>$v)
                                                    <label class="css-input css-radio css-radio-primary css-radio-sm push-10-r">
                                                        <input type="radio" name="{{$systemConfig['name']}}"
                                                               value="{{$k}}"
                                                               @if($systemConfig['value']==$k) checked @endif>
                                                        <span></span> {{$v}}
                                                    </label>
                                                @endforeach
                                                <span class="form-control-static form-option-line help-line">{{$systemConfig['tips']}}</span>
                                            </div>
                                        @elseif($systemConfig['genre']=='checkbox')
                                            <div class="col-md-11 form-option-line">
                                                @foreach(json_decode($systemConfig['options'],true) as $k=>$v)
                                                    <label class="css-input css-checkbox css-checkbox-primary css-checkbox-sm css-checkbox-rounded">
                                                        <input type="checkbox" name="{{$systemConfig['name']}}"
                                                               value="{{$k}}"
                                                               @if(in_array($k,explode(',',$systemConfig['value']))) checked @endif>
                                                        <span></span> {{$v}}
                                                    </label>
                                                @endforeach
                                                <span class="form-control-static form-option-line help-line">{{$systemConfig['tips']}}</span>
                                            </div>
                                        @elseif($systemConfig['genre']=='select')
                                            <div class="col-md-6 form-option-line">
                                                <select class="form-control" name="{{$systemConfig['name']}}">
                                                    <option value="">请选择：</option>
                                                    @foreach(json_decode($systemConfig['options'],true) as $k=>$v)
                                                        <option value="{{$k}}"
                                                                @if($systemConfig['value']==$k) selected @endif>{{$v}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='switch')
                                            <div class="col-md-11 form-option-line">
                                                <label class="css-input switch switch-sm switch-primary switch-rounded "
                                                       title="开启/关闭">
                                                    <input type="checkbox" name="{{$systemConfig['name']}}" value="1"
                                                           @if($systemConfig['value']) checked @endif><span></span>
                                                </label>
                                                <span class="form-control-static form-option-line help-line">{{$systemConfig['tips']}}</span>
                                            </div>
                                        @elseif($systemConfig['genre']=='select2')
                                            <div class="col-md-6 form-option-line">
                                                <select class="js-select2 form-control select-linkage select2-hidden-accessible"
                                                        name="{{$systemConfig['name']}}" aria-hidden="true">
                                                    <option value="">请选择</option>
                                                    @foreach(json_decode($systemConfig['options'],true) as $k=>$v)
                                                        <option value="{{$k}}"
                                                                @if($systemConfig['value']==$k) selected @endif>{{$v}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='image')
                                            <div class="col-md-11 form-option-line">
                                                <div class="webuploader-box js-upload-image" upload-type="image">
                                                    <input type="hidden" name="{{$systemConfig['name']}}"
                                                           value="{{$systemConfig['value']}}">
                                                    <div class="uploader-list">
                                                        @if($systemConfig['value']!=='')
                                                            <div class="file-item js-gallery thumbnail upload-state-done">
                                                                <img class="uploader-img" src="{{$systemConfig['value']}}"
                                                                     data-original="{{$systemConfig['value']}}">
                                                                <div class="info"></div>
                                                                <i class="fa fa-times-circle remove-picture"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="filePicker">上传单张图片</div>
                                                    <span class="form-control-static form-option-line help-line form-option-webuploader-line">{{$systemConfig['tips']}}</span>
                                                </div>
                                            </div>
                                        @elseif($systemConfig['genre']=='images')
                                            <div class="col-md-11 form-option-line">
                                                <div class="webuploader-box js-upload-image" upload-type="images"
                                                     input-name="{{$systemConfig['name']}}">
                                                    <div class="uploader-list ui-images-sortable">
                                                        @foreach(explode(',',$systemConfig['value']) as $v)
                                                            <div class="file-item js-gallery thumbnail upload-state-done">
                                                                <img class="uploader-img" src="{{$v}}" data-original="{{$v}}">
                                                                <input type="hidden" name="{{$systemConfig['name']}}[]"
                                                                       value="{{$v}}">
                                                                <i class="fa fa-times-circle remove-picture"></i>
                                                                <i class="fa fa-fw fa-arrows move-picture"></i>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="filePicker">上传多张图片</div>
                                                    <span class="form-control-static form-option-line help-line form-option-webuploader-line">{{$systemConfig['tips']}}</span>
                                                </div>
                                            </div>
                                        @elseif($systemConfig['genre']=='file')
                                            <div class="col-md-11 form-option-line">
                                                <div class="webuploader-box js-upload-file" upload-type="file">
                                                    <input type="hidden" name="{{$systemConfig['name']}}"
                                                           value="{{$systemConfig['value']}}">
                                                    <div class="uploader-list">
                                                        <li class="list-group-item file-item upload-state-done"
                                                            style="word-wrap: break-word;">
                                                            <span class="pull-right file-state"></span>
                                                            <i class="fa fa-file"></i>{{$systemConfig['value']}}&nbsp;&nbsp;
                                                            <span class="file-btns">
                                                                [<a href="javascript:void(0);"
                                                                    class="remove-file">删除</a>]&nbsp;
                                                                [<a href="{{$systemConfig['value']}}" target="_blank"
                                                                    class="text-success">下载</a>]
                                                            </span>
                                                        </li>
                                                    </div>
                                                    <div class="filePicker">上传单个文件</div>
                                                    <span class="form-control-static form-option-line help-line form-option-webuploader-line">{{$systemConfig['tips']}}</span>
                                                </div>
                                            </div>
                                        @elseif($systemConfig['genre']=='files')
                                            <div class="col-md-11 form-option-line">
                                                <div class="webuploader-box js-upload-file" upload-type="files"
                                                     input-name="{{$systemConfig['name']}}">
                                                    <div class="uploader-list ui-files-sortable">
                                                        @foreach(explode(',',$systemConfig['value']) as $v)
                                                            <li class="list-group-item file-item upload-state-done"
                                                                style="word-wrap: break-word;">
                                                                <span class="pull-right file-state"></span>
                                                                <i class="fa fa-file"></i>{{$v}}&nbsp;&nbsp;
                                                                <span class="file-btns">
                                                                [<a href="javascript:void(0);"
                                                                    class="remove-file">删除</a>]&nbsp;
                                                                [<a href="{{$v}}" target="_blank" class="text-success">下载</a>]
                                                            </span>
                                                                <input type="hidden" name="{{$systemConfig['name']}}[]"
                                                                       value="{{$v}}">
                                                                <i class="fa fa-fw fa-arrows move-file"
                                                                   style="display: none;"></i>
                                                            </li>
                                                        @endforeach
                                                    </div>
                                                    <div class="filePicker">上传多个文件</div>
                                                    <span class="form-control-static form-option-line help-line form-option-webuploader-line">{{$systemConfig['tips']}}</span>
                                                </div>
                                            </div>
                                        @elseif($systemConfig['genre']=='date')
                                            <div class="col-md-6 form-option-line">
                                                <input class="js-datepicker form-control form-datepicker"
                                                       autocomplete="off" type="text" name="{{$systemConfig['name']}}"
                                                       value="{{$systemConfig['value']}}" data-date-format="yyyy-mm-dd"
                                                       placeholder="请选择或者输入日期，输入格式：yyyy-mm-dd">
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='datetime')
                                            <div class="col-md-6 form-option-line">
                                                <input class="js-datetimepicker form-control form-datetimepicker"
                                                       autocomplete="off" type="text" name="{{$systemConfig['name']}}"
                                                       value="{{$systemConfig['value']}}"
                                                       placeholder="请选择或者输入日期时间，输入格式：YYYY-MM-DD HH:mm"
                                                       data-side-by-side="true" data-locale="zh-cn"
                                                       data-format="YYYY-MM-DD HH:mm">
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='ueditor')
                                            <div class="col-md-9 form-option-line">
                                                <script class="js-ueditor" name="{{$systemConfig['name']}}"
                                                        type="text/plain">{{$systemConfig['value']}}</script>
                                            </div>
                                            <div class="col-md-11 col-md-offset-1 form-validate-msg form-option-line">
                                                <span class="form-control-static help-line">{{$systemConfig['tips']}}</span>
                                            </div>
                                        @elseif($systemConfig['genre']=='tags')
                                            <div class="col-md-6 form-option-line">
                                                <input class="form-control tags-input" type="text"
                                                       name="{{$systemConfig['name']}}"
                                                       value="{{$systemConfig['value']}}"
                                                       placeholder="请输入{{$systemConfig['title']}}">
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @elseif($systemConfig['genre']=='icon')
                                            <div class="col-md-6 form-option-line">
                                                <div class="input-group js-icon-picke">
                                                    <input name="{{$systemConfig['name']}}" placeholder="请选择图标"
                                                           data-placement="bottomRight" data-input-search="true"
                                                           class="form-control icp icp-auto"
                                                           value="{{str_replace('fa ','',$systemConfig['value'])}}"
                                                           type="text"/>
                                                    <span class="input-group-addon"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-5 form-control-static form-option-line">
                                                <div class="help-block help-block-line">{{$systemConfig['tips']}}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="config-name">
                                    <div class="col-md-offset-2 col-md-9">
                                        <button class="btn btn-minw btn-primary ajax-post" type="button"
                                                id="config-submit">
                                            保存
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
    @if(in_array('select2',$genres))
        <script src="{{asset('/static/libs/select2/select2.full.min.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/select2/i18n/zh-CN.js').'?'.$SFV}}"></script>
        <script>
            $(function () {
                App.initHelpers('select2');
            });
        </script>
    @endif
    @if(in_array('tags',$genres))
        <script src="{{asset('/static/libs/jquery-tags-input/jquery.tagsinput.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/admin/js/tags-input.js').'?'.$SFV}}"></script>
    @endif
    @if(in_array('image',$genres) || in_array('images',$genres) || in_array('file',$genres) || in_array('files',$genres))
        @if(in_array('images',$genres) || in_array('files',$genres))
            <script src="{{asset('/static/libs/jquery-nestable/jquery.nestable.js').'?'.$SFV}}"></script>
            <script src="{{asset('/static/libs/jquery-ui/jquery-ui.min.js').'?'.$SFV}}"></script>
            <script src="{{asset('/static/admin/js/webuploader-move.js').'?'.$SFV}}"></script>
        @endif
        <script src="{{asset('/static/libs/webuploader/webuploader.min.js').'?'.$SFV}}"></script>
        @if(in_array('image',$genres) || in_array('images',$genres))
            <script src="{{asset('/static/libs/viewer/viewer.min.js').'?'.$SFV}}"></script>
            @if($type=='admin')
                <script>
                    var set_scene_uploader_image = ['set_admin_logo', 'set_admin_logo_text', 'set_admin_logo_signin'];
                </script>
            @elseif($type=='upload')
                <script>
                    var set_scene_uploader_image = ['set_upload_image_watermark'];
                </script>
            @else
                <script>
                    var set_scene_uploader_image = [];
                </script>
            @endif
            <script src="{{asset('/static/admin/js/webuploader-image.js').'?'.$SFV}}"></script>
        @else
            @if($type=='admin')
                <script>
                    var set_scene_uploader_file = [];
                </script>
            @elseif($type=='upload')
                <script>
                    var set_scene_uploader_file = [];
                </script>
            @else
                <script>
                    var set_scene_uploader_file = [];
                </script>
            @endif
            <script src="{{asset('/static/admin/js/webuploader-file.js').'?'.$SFV}}"></script>
        @endif
    @endif
    @if(in_array('date',$genres))
        <script src="{{asset('/static/libs/bootstrap-datepicker/bootstrap-datepicker.min.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/bootstrap-datepicker/locales/bootstrap-datepicker.zh-CN.min.js').'?'.$SFV}}"></script>
        <script>
            $(function () {
                App.initHelpers('datepicker');
            });
        </script>
    @endif
    @if(in_array('datetime',$genres))
        <script src="{{asset('/static/libs/bootstrap-datetimepicker/moment.min.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/bootstrap-datetimepicker/locale/zh-cn.js').'?'.$SFV}}"></script>
        <script>
            $(function () {
                App.initHelpers('datetimepicker');
            });
        </script>
    @endif
    @if(in_array('ueditor',$genres))
        <script src="{{asset('/static/libs/ueditor/ueditor.config.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/ueditor/ueditor.all.min.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/libs/ueditor/lang/zh-cn/zh-cn.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/admin/js/ueditor-handle.js').'?'.$SFV}}"></script>
    @endif
    @if(in_array('icon',$genres))
        <script src="{{asset('/static/libs/fontawesome-iconpicker/fontawesome-iconpicker.js').'?'.$SFV}}"></script>
        <script src="{{asset('/static/admin/js/iconpicker.js').'?'.$SFV}}"></script>
    @endif
    <script>
        $(function () {
            $(document).on('click', '#config-submit', function () {
                $('#config-form').find('.form-validate-msg').remove();//清空该表单的验证错误信息
                data = $('#config-form').serialize();
                Dolphin.loading('保存中...');
                $.ajax({
                    type: 'POST',
                    url: '{{action('Admin\System\IndexController@config',['type'=>$type])}}',
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
                                $('#config-' + k).append(validate_tips); // 页面表单项下方提示，错误验证信息
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

