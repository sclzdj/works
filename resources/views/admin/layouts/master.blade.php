<!DOCTYPE html>
<!--[if IE 9]>
<html class="ie9 no-focus" lang="zh"> <![endif]-->
<!--[if gt IE 9]><!-->
<html class="no-focus" lang="zh"> <!--<![endif]-->
@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
    $upload_default_filesystems=config('filesystems.default');
    $config_upload_image_limit_size=\App\Model\Admin\SystemConfig::getVal('upload_image_limit_size', 'upload');
    $config_upload_image_allow_extension=\App\Model\Admin\SystemConfig::getVal('upload_image_allow_extension', 'upload');
    $config_upload_file_limit_size=\App\Model\Admin\SystemConfig::getVal('upload_file_limit_size', 'upload');
    $config_upload_file_allow_extension=\App\Model\Admin\SystemConfig::getVal('upload_file_allow_extension', 'upload');
@endphp
<head>
    <meta charset="utf-8">
    <title>
        @php
            $admin_name=\App\Model\Admin\SystemConfig::getVal('admin_name','admin');
        @endphp
        @yield('page_title','后台 | '.($admin_name!==''?$admin_name:config('app.name')))
    </title>
    <meta name="keywords" content="{{\App\Model\Admin\SystemConfig::getVal('admin_keywords','admin')}}">
    <meta name="description" content="{{\App\Model\Admin\SystemConfig::getVal('admin_describe','admin')}}">
    <meta name="author" content="DuJun">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1.0,user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Icons -->
    <!-- 下面的图标可以用自己的图标替换，它们被桌面和移动浏览器所使用 -->
    @php
        $admin_logo=\App\Model\Admin\SystemConfig::getVal('admin_logo','admin');
    @endphp
    <link rel="shortcut icon" type="image/png"
          href="{{$admin_logo!==''?$admin_logo:asset('/static/admin/img/logo.png'.'?'.$SFV)}}" sizes="40x40">
    <link rel="icon" type="image/png"
          href="{{$admin_logo!==''?$admin_logo:asset('/static/admin/img/logo.png'.'?'.$SFV)}}" sizes="40x40">
    <link rel="apple-touch-icon" type="image/png"
          href="{{$admin_logo!==''?$admin_logo:asset('/static/admin/img/logo.png'.'?'.$SFV)}}" sizes="40x40">
    <!-- END Icons -->
    <!-- Stylesheets -->
    <!--本页面专属顶部css-->
@yield('pre_css')
<!-- Bootstrap与ONEUI CSS框架 -->
    <link rel="stylesheet" href="{{asset('/static/admin/css/font-awesome.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('static/admin/css/bootstrap.min.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('static/admin/css/oneui.css').'?'.$SFV}}">
    <link rel="stylesheet" href="{{asset('static/admin/css/dolphin.css').'?'.$SFV}}">
    <!--自定义css-->
    <link rel="stylesheet" href="{{asset('/static/admin/css/custom.css').'?'.$SFV}}">
    <!--本页面专属底部css-->
@yield('css')
<!-- END Stylesheets -->
</head>
<body>
<!-- Page Container -->
<div id="page-container" class="sidebar-l sidebar-o side-scroll header-navbar-fixed ">
    <!-- Side Overlay-->
@include('admin.layouts.side-overlay')
<!-- END Side Overlay -->
    <!-- Sidebar -->
@include('admin.layouts.sidebar')
<!-- END Sidebar -->
    <!-- Header -->
@include('admin.layouts.header-navbar')
<!-- END Header -->
    <!-- Main Container -->
    <main id="main-container">
        <!-- Page Header -->
    @include('admin.layouts.location-navbar')
    <!-- END Page Header -->
        <!-- Page Content -->
        <div class="content" id="app">
            @yield('content','')
        </div>
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->
    <!-- Footer -->
@include('admin.layouts.page-footer')
<!-- END Footer -->
</div>
<!-- END Page Container -->
<!-- Apps Modal -->
<!-- Opens from the button in the header -->
@include('admin.layouts.apps-modal')
<!-- END Apps Modal -->
<!-- Page JS Plugins -->
<script src="{{asset('/static/admin/js/core/jquery.min.js').'?'.$SFV}}"></script>
<!--vuejs引入和相关代码-->
@yield('vuejs','')
<script src="{{asset('/static/admin/js/core/bootstrap.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/admin/js/core/jquery.slimscroll.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/admin/js/core/jquery.scrollLock.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/admin/js/core/jquery.placeholder.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/admin/js/app.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/admin/js/dolphin.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/libs/bootstrap-notify/bootstrap-notify.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/libs/js-xss/xss.min.js').'?'.$SFV}}"></script>
<script src="{{asset('/static/libs/layer/layer.js').'?'.$SFV}}"></script>
<!--前置自定义js-->
<script src="{{asset('/static/admin/js/pre_custom.js').'?'.$SFV}}"></script>
<!-- 程序启动 -->
<script>
    jQuery(function () {
        App.initHelpers(['appear', 'slimscroll', 'magnific-popup', 'table-tools']);
        $(document).on('click', '[data-toggle="layout"]', function () {
            App.layout($(this).attr('data-action'));
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
    //上传全局配置
    var server_upload_image_special_scenes={!! json_encode(config('custom.upload_image_special_scenes')) !!};//文件上传特殊场景配置，这些场景会在上传时做特殊处理，不会生成水印和缩略图
    var server_upload_default_filesystems="{{$upload_default_filesystems}}";//磨人的文件上传系统
    var server_upload_image_url = "{{action('Admin\System\FileController@upload')}}";//上传地址
    var server_image_host = "";//图片显示前缀域名，上传成功后返回的是完整图片地址就留空
    var server_upload_image_limit_size = "{{$config_upload_image_limit_size}}";//允许的上传图片大小
    server_upload_image_limit_size = server_upload_image_limit_size === '' ? 0 : parseInt(server_upload_image_limit_size) * 1000;
    var server_upload_image_allow_extension = "{{$config_upload_image_allow_extension}}";//允许的上传图片后缀
    var server_upload_file_url = "{{action('Admin\System\FileController@upload')}}";//上传地址
    var server_file_host = "";//文件显示前缀域名，上传成功后返回的是完整文件地址就留空
    var server_upload_file_limit_size = "{{$config_upload_file_limit_size}}";//允许的上传文件大小
    server_upload_file_limit_size = server_upload_file_limit_size === '' ? 0 : parseInt(server_upload_file_limit_size) * 1000;
    var server_upload_file_allow_extension = "{{$config_upload_file_allow_extension}}";//允许的上传文件后缀
</script>
<!--自定义js-->
<script src="{{asset('/static/admin/js/custom.js').'?'.$SFV}}"></script>
<script>
    $(function () {
        $(document).on('click', '#admin-logout', function () {
            Dolphin.loading('退出中...');
            $.ajax({
                type: 'POST',
                url: '/admin/logout',
                dataType: 'JSON',
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
                    if (xhr.status == 419) { // csrf错误，错误码固定为419
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
        //侧栏开关
        $(document).on('change', 'input.side-switch', function () {
            var name = $(this).prop('name');
            var checked = $(this).is(':checked');
            if (checked) {
                var value = 1;
            } else {
                var value = 0;
            }
            var _this = $(this);
            Dolphin.loading('设置中');
            $.ajax({
                type: 'PUT',
                url: '{{action('Admin\System\IndexController@config')}}',
                data: {name: name, value: value},
                dataType: 'JSON',
                success: function (response) {
                    Dolphin.loading('hide');
                    if (response.status_code >= 200 && response.status_code < 300) {
                        Dolphin.lNotify(response.message, 'success');
                    } else {
                        _this.prop('checked', !checked);
                        Dolphin.notify(response.message, 'danger');
                    }
                },
                error: function (xhr, status, error) {
                    _this.prop('checked', !checked);
                    var response = JSON.parse(xhr.responseText);
                    Dolphin.loading('hide');
                    if (xhr.status == 419) { // csrf错误，错误码固定为419
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
            return false;
        });
    });
</script>
<!--本页面专属js-->
@yield('javascript','')
</body>
</html>
