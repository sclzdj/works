@extends('admin.layouts.master')

@section('css')

    <!-- 引入样式 -->
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
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
                    <h3 class="block-title">邀请码管理</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <el-table
                                    :data="data"
                                    style="width: 100%">
                                <el-table-column
                                        prop="code"
                                        label="邀请码"
                                        width="180">
                                </el-table-column>
                                <el-table-column
                                        prop="type"
                                        label="邀请码类型"
                                        width="180">
                                </el-table-column>
                                <el-table-column
                                        prop="status"
                                        label="状态">
                                </el-table-column>

                                <el-table-column
                                        prop="created_at"
                                        label="创建时间">
                                </el-table-column>

                                <el-table-column
                                        fixed="right"
                                        label="操作"
                                        width="100">
                                    <template slot-scope="scope">
                                        <el-button type="text" size="small">占用</el-button>
                                    </template>
                                </el-table-column>

                            </el-table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('javascript')

    <!-- 引入组件库 -->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/element-ui/lib/index.js"></script>

    <script>

        Vue.config.devtools = true;
        var vm = new Vue({
            el: '#app',
            data: {
                 data:[]
            },
            methods: {
                init: function () {
                    var that = this;
                    var data = {};
                    $.ajax({
                        type: 'GET',
                        url: '/admin/invotecode/lists',
                        data: data,
                        success: function (response) {
                            that.data = response.data;
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
            computed: {

            }
        });


    </script>
@endsection
