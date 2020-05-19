@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')

@section('css')

    <!-- 引入样式 -->
    <link rel="stylesheet" href="{{asset('/static/admin/css/element.css').'?'.$SFV}}">
    <style>
        .wd {
            width: 150px;
        }

        .el-row {
            margin-bottom: 20px;

        &
        :last-child {
            margin-bottom: 0;
        }

        }
        .el-col {
            border-radius: 4px;
        }

        .bg-purple-dark {
            background: #99a9bf;
        }

        .bg-purple {
            background: #d3dce6;
        }

        .bg-purple-light {
            background: #e5e9f2;
        }

        .grid-content {
            border-radius: 4px;
            min-height: 36px;
        }

        .row-bg {
            padding: 10px 0;
            background-color: #f9fafc;
        }

        .button1 {
            display: inline-block;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            background: #FFF;
            border: 1px solid #DCDFE6;
            color: #606266;
            -webkit-appearance: none;
            text-align: center;
            box-sizing: border-box;
            outline: 0;
            margin: 0;
            -webkit-transition: .1s;
            transition: .1s;
            font-weight: 500;
            padding: 12px 10px;
            font-size: 14px;
            border-radius: 4px;
            width: 50px;
        }

    </style>
    <style>
        .avatar-uploader .el-upload {
            border: 1px dashed #d9d9d9;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .avatar-uploader .el-upload:hover {
            border-color: #409EFF;
        }

        .avatar-uploader-icon {
            font-size: 28px;
            color: #8c939d;
            width: 178px;
            height: 178px;
            line-height: 178px;
            text-align: center;
        }

        .avatar {
            width: 178px;
            height: 178px;
            display: block;
        }

        .el-upload__input {
            display: none !important
        }
    </style>

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
                    <h3 class="block-title">编辑标签</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">




                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        标签名
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.name" placeholder="请输入内容"
                                                  style='width: 550px'></el-input>
                                        </button>
                                    </div>
                                </div>



                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">


                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>

                                    </label>
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        <el-button @click="submit" type="primary">保存</el-button>




                                    </label>

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

    <!-- 引入组件库 -->
    <script src="{{asset('/static/admin/js/vue.js').'?'.$SFV}}"></script>
    <script src="{{asset('/static/admin/js/element.js').'?'.$SFV}}"></script>
    <script>
        var id = "<?php echo $id;?>";
        Vue.config.devtools = true;
        var vm = new Vue({
            el: '#app',
            data: {
                input: "",
                crowdFunding: {},
                form: {
                    name: "",

                }
            },
            methods: {

                submit() {
                    var that = this;
                    var data = {
                        form: this.form
                    };
                    $.ajax({
                        url: '/admin/helptags/'+this.form.id,
                        method: 'PUT',
                        data: data,
                        success: function (response) {
                            if (response.result == false) {
                                alert(response.msg);
                            } else {
                                window.location.href = "/admin/helptags";
                            }
                        },
                        error: function (xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);
                            if (xhr.status == 419) { // csrf错误，错误码固定为419
                                alert('请勿重复请求~');
                            } else if (xhr.status == 422) { // 验证错误
                                var message = [];
                                for (var i in response.errors) {
                                    message = message.concat(response.errors[i]);
                                }
                                message = message.join(',');
                                alert(message);
                            } else {
                                if (response.message) {
                                    alert(response.message);
                                } else {
                                    alert('服务器错误~');
                                }
                            }
                        }
                    });
                },
                init: function (page) {
                    var that = this;
                    var data = {
                        id: id,
                    };
                    $.ajax({
                        type: 'GET',
                        url: '/admin/helptags/lists',
                        data: data,
                        success: function (response) {
                            that.form = response.template;
                        },
                        error: function (xhr, status, error) {
                            var response = JSON.parse(xhr.responseText);
                            if (xhr.status == 419) { // csrf错误，错误码固定为419
                                alert('请勿重复请求~');
                            } else if (xhr.status == 422) { // 验证错误
                                var message = [];
                                for (var i in response.errors) {
                                    message = message.concat(response.errors[i]);
                                }
                                message = message.join(',');
                                alert(message);
                            } else {
                                if (response.message) {
                                    alert(response.message);
                                } else {
                                    alert('服务器错误~');
                                }
                            }
                        }
                    });
                },

            },
            mounted: function () {
                this.init();
            },
            watch: {
                form: {
                    handler: function () {
                        // 发送名字不能有中文

                    },
                    deep: true
                }
            }
        });


    </script>
@endsection
