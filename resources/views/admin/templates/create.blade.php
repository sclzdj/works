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
                    <h3 class="block-title">海报管理添加</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">


                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        序号
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        用途
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.purpose" placeholder="请输入内容"
                                                  style='width: 550px'></el-input>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        文案一
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.text1" placeholder="请输入内容"
                                                  style='width: 550px'>

                                        </el-input>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        文案二
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.text2" placeholder="请输入内容"
                                                  style='width: 550px'>

                                        </el-input>

                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        文案三
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.text3" placeholder="请输入内容"
                                                  style='width: 550px'>

                                        </el-input>

                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        文案四
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.text4" placeholder="请输入内容"
                                                  style='width: 550px'>

                                        </el-input>

                                    </div>
                                </div>


                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        上传背景图
                                    </label>

                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-upload
                                                class="avatar-uploader"
                                                action="https://zuopin.cloud/api/star/upload"
                                                :show-file-list="false"
                                                :on-success="handleAvatarSuccess"
                                                :before-upload="beforeAvatarUpload">
                                            <img v-if="form.background" :src="form.background" class="avatar">
                                            <i v-else class="el-icon-plus avatar-uploader-icon"></i>
                                        </el-upload>
                                    </div>

                                </div>
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">


                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>

                                    </label>
                                    <label class="col-md-4 control-label form-option-line">
                                        <span></span>
                                        <el-button @click="submit" type="primary">保存</el-button>

                                        {{--                                        <el-button @click="help">帮助</el-button>--}}

                                        <el-tooltip placement="top">
                                            <div slot="content">
                                                <span style="font-size: 20px">
                                                ##money##       项目金额<br/>
                                                ##number##      成品张数<br/>
                                                ##time##        拍摄时长<br/>
                                                ##customer##    客户名称<br/>
                                                ##name##        用户姓名<br/>
                                                ##title##       用户头衔<br/>
                                                </span>
                                            </div>
                                            <el-button>帮助</el-button>
                                        </el-tooltip>

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

        Vue.config.devtools = true;
        var vm = new Vue({
                el: '#app',
                data: {
                    input: "",
                    crowdFunding: {},
                    form: {
                        number: 0,
                        purpose: "",
                        text1: "",
                        text2: "",
                        text3: "",
                        text4: "",
                        background: "",
                    }
                },
                methods: {
                    handleAvatarSuccess(res, file) {
                        this.form.background = res.data.url;
                    },
                    beforeAvatarUpload(file) {
                        const isJPG = file.type === 'image/jpeg';
                        const isLt2M = file.size / 1024 / 1024 < 2;
                        if (!isLt2M) {
                            this.$message.error('上传头像图片大小不能超过 2MB!');
                        }
                        return isLt2M;
                    },
                    submit() {
                        var that = this;
                        var data = {
                            form: this.form
                        };
                        $.ajax({
                            type: 'Post',
                            url: '/admin/templates',
                            data: data,
                            success: function (response) {
                                if (response.result == false) {
                                    alert(response.msg);
                                } else {
                                    window.location.href = "/admin/templates";
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
                    mounted: function () {

                    },
                },
                watch: {
                    form: {
                        handler: function () {
                            // 发送名字不能有中文
                            for (let i = 0; i < this.form.number.length; i++) {
                                let char_code_at_i = this.form.number.charCodeAt(i);
                                console.log(char_code_at_i);
                                if ((char_code_at_i >= 48 && char_code_at_i <= 57)) {
                                } else {
                                    this.form.number = this.form.number.substr(0, i) + this.form.number.substr(i + 1);
                                }
                            }
                        },
                        deep: true
                    }
                }
            }
        )


    </script>
@endsection
