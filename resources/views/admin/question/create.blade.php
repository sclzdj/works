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
                    <h3 class="block-title">问题反馈编辑</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">

                            <el-form ref="form" :rules="rules" :model="form" label-width="400px">

                                <el-form-item label="问题类型">
                                    <el-select style="width: 60%" v-model="form.type" placeholder="">
                                        <el-option label="bug" value="1"></el-option>
                                        <el-option label="建议" value="2"></el-option>
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="问题页面">
                                    <el-select style="width: 60%" v-model="form.page" placeholder="请选择">
                                        <el-option
                                                v-for="item in pages"
                                                :key="item"
                                                :label="item"
                                                :value="item">
                                        </el-option>
                                    </el-select>
                                </el-form-item>

                                <el-form-item label="手机型号" label-width="400px">
                                    <el-input style="width: 60%" v-model="form.mobile_version"></el-input>
                                </el-form-item>

                                <el-form-item label="系统版本" label-width="400px">
                                    <el-input style="width: 60%" v-model="form.system_version"></el-input>
                                </el-form-item>

                                <el-form-item label="微信版本" label-width="400px">
                                    <el-input style="width: 60%" v-model="form.wechat_version"></el-input>
                                </el-form-item>


                                <el-form-item label="语言" label-width="400px">
                                    <el-input style="width: 60%" v-model="form.language"></el-input>
                                </el-form-item>


                                <el-form-item label="意见"  prop="content" label-width="400px">
                                    <el-input type="textarea" style="width: 60%" maxlength="255"
                                              :autosize="{ minRows: 4, maxRows: 8}" v-model="form.content"></el-input>
                                </el-form-item>


                                <el-form-item label="是否标星" label-width="400px">
                                    <el-checkbox v-model="form.important"></el-checkbox>
                                </el-form-item>


                                <el-form-item label="关联用户" label-width="400px">
                                    <el-select v-model="form.users" style="width: 60%" filterable multiple
                                               placeholder="请选择">
                                        <el-option
                                                v-for="key,value in users"
                                                :key="value"
                                                :label="key"
                                                :value="value">
                                        </el-option>
                                    </el-select>
                                </el-form-item>


                                <el-form-item>
                                    <el-button type="primary" @click="submit('form')">立即创建</el-button>
                                    <el-button @click="clear">返回</el-button>
                                </el-form-item>
                            </el-form>

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

        Vue.config.devtools = true;
        var vm = new Vue({
            el: '#app',
            data: {
                pages: [
                    '创建-创建云作品',
                    '添加-从手机相册选图',
                    '添加-从百度网盘选图',
                    '添加-添加/修改项目信息',
                    '展示-自己的作品/项目/合集',
                    '展示-别人的作品/项目/合集',
                    '分享-发给微信好友',
                    '分享-生成获客海报',
                    '分享-生成小程序码',
                    '人脉-访客登录',
                    '人脉-访客列表',
                    '人脉-访客详情',
                    '其他-最近浏览',
                    '其他-人脉排行榜',
                    '其他-修改个人资料',
                    '其他-学习使用技巧',
                    '其他'
                ],
                form: {
                    page: "其他",
                    type: "1",
                    content: "",
                    users: [],
                    mobile_version: "",
                    system_version: "",
                    wechat_version: "",
                    language: "",
                    important: false
                },
                users: [],
                rules: {
                    content: [
                        {
                            required: true, message: '意见必须输入', trigger: 'blur'
                        },
                    ],

                }
            },
            methods: {
                clear() {
                    window.location.href = "/admin/question";
                },
                submit(formName) {
                    var that = this;
                    this.$refs[formName].validate((valid) => {
                        if (valid) {
                            var data = {
                                form: that.form
                            };
                            $.ajax({
                                url: '/admin/question',
                                method: 'post',
                                data: data,
                                success: function (response) {
                                    window.location.href = "/admin/question";
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
                        } else {

                            this.$message.error("请检查表单错误");
                            return false;
                        }
                    });
                },
                init() {
                    var that = this;

                    $.ajax({
                        url: '/admin/question/lists',
                        method: 'get',

                        success: function (response) {
                            that.users = response.user;
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
                }
            },
            mounted: function () {
                this.init();
            },

        });


    </script>
@endsection
