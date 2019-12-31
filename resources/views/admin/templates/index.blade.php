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
        }

        :last-child {
            margin-bottom: 0;
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
                    <h3 class="block-title">模版管理</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="block-content">

                        <el-button type="primary" @click="create" icon="el-icon-search">创建</el-button>

                    </div>
                    <div class="tab-pane active">

                        <div class="block-content">
                            <el-table
                                :data="data"
                                style="width: 100%"

                            >

                                <el-table-column
                                    prop="number"
                                    label="序号"
                                    width="180">
                                </el-table-column>
                                <el-table-column
                                    prop="text1"
                                    label="文案1"
                                    width="180">
                                </el-table-column>
                                <el-table-column
                                    prop="purpose"
                                    label="用途">
                                </el-table-column>

                                <el-table-column
                                    prop="created_at"
                                    label="创建时间">
                                </el-table-column>
                                <el-table-column fixed="right" label="操作" width="100">
                                    <template slot-scope="scope">
                                        <el-button @click="handleShow(scope.row)" type="text" size="small">预览
                                        </el-button>
                                        <el-button @click="handleDelete(scope.$index, scope.row)" type="text"
                                                   size="small">删除
                                        </el-button>

                                        <el-button @click="handleEdit(scope.$index, scope.row)" type="text"
                                                                                           size="small">编辑
                                         </el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <div class="page">
                                <page :total="total" :page-size="size" @navpage="init" ref="children"></page>
                            </div>
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

        Vue.component("page", {
            template: `
        <div class="text-right" style="width:100%; margin-top: 1%" v-if="pages===0||pages===1?false:true">
              <ul class="pagination" style="margin:0px 0px 50px 0px;">
                <li v-on:click.stop.prevent="pageChange(pageNo==1?1:pageNo-1)" v-bind:class="{disabled:pageNo===1}">
                    <a href="javascript:void(0);">上一页</a>
                </li>
                <li @click.stop.prevent="pageChange(1)" v-bind:class="{active:pageNo===1}" v-if="{false:pageNo===1}">
                    <a>1</a>
                </li>
                <li @click.stop.prevent="pageChange(pageNo - display)" v-if="showJumpPrev">
                    <a style="font-weight:900;">&laquo;</a>
                </li>

                <li v-for="page in pagingCounts" @click.stop.prevent="pageChange(page)" v-bind:class="{active:pageNo===page}">
                    <a>@{{page}}</a>
                </li>

                <li @click.stop.prevent="pageChange(pageNo + display)" v-if="showJumpNext">
                    <a style="font-weight:900;">&raquo;</a>
                </li>

                <li @click.stop.prevent="pageChange(pages)" v-bind:class="{active:pageNo===pages}" v-if="pages===0||pages===1?false:true">
                    <a>@{{pages}}</a>
                </li>
                <li v-on:click.stop.prevent="pageChange(pageNo==pages?pages:pageNo+1)" v-bind:class="{disabled:pageNo===pages}">
                     <a href="javascript:void(0);">下一页</a>
                </li>
                <li class="disabled"><a href="javascript:void(0);">@{{total}}条记录</a>
                </li>
              </ul>
        </div>
            `,
            data: function () {
                return {
                    // 当前页
                    pageNo: 1,
                    // 总页数
                    pages: 0
                }
            },
            props: {
                display: {// 显示页数
                    type: Number,
                    default: 5,
                    required: false
                },
                total: {// 总记录数
                    type: Number,
                    default: 1
                },
                pageSize: {// 每页显示条数
                    type: Number,
                    default: 10,
                    required: false
                }
            },
            created: function () {// 生命周期函数，创建时计算总页数
                let that = this;
                this.pages = Math.ceil(that.total / that.pageSize)
            },
            methods: {
                pageChange: function (page) {
                    if (this.pageNo === page) {
                        return;
                    }
                    this.pageNo = page;
                    this.$emit('navpage', this.pageNo);
                },
                initPageNo: function () {
                    this.pageNo = 1;
                }
            },
            computed: {
                numOffset() {
                    return Math.floor((this.display + 2) / 2) - 1;
                },
                showJumpPrev() {
                    if (this.total > this.display + 2) {
                        if (this.pageNo > this.display) {
                            return true
                        }
                    }
                    return false
                },
                showJumpNext() {
                    if (this.pages > this.display + 2) {
                        if (this.pageNo <= this.pages - this.display) {
                            return true
                        }
                    }
                    return false
                },
                // 当前要显示的数字按钮集合
                pagingCounts() {
                    let that = this,
                        startNum,
                        result = [],
                        showJumpPrev = that.showJumpPrev,
                        showJumpNext = that.showJumpNext;
                    if (showJumpPrev && !showJumpNext) {
                        startNum = that.pages - that.display;
                        for (let i = startNum; i < that.pages; i++) {
                            result.push(i);
                        }
                    } else if (!showJumpPrev && showJumpNext) {
                        for (let i = 2; i < that.display + 2; i++) {
                            result.push(i);
                        }
                    } else if (showJumpPrev && showJumpNext) {
                        for (let i = that.pageNo - that.numOffset; i <= that.pageNo + that.numOffset; i++) {
                            result.push(i);
                        }
                    } else {
                        for (let i = 2; i < that.pages; i++) {
                            result.push(i);
                        }
                    }
                    return result
                }
            },
            watch: {
                total: {
                    handler: function () {
                        let that = this;
                        this.pages = Math.ceil(that.total / that.pageSize)
                    }
                }
            },
        });

        Vue.config.devtools = true;
        var vm = new Vue({
            el: '#app',
            data: {
                number: 0,
                data: [],
                tableData: [],
                size: 20,
                total: 0,
                form: {
                    type: 0,
                    status: -1,
                    is_send: -1,
                    created_at: []
                },

                multipleSelection: []
            },
            methods: {
                init: function (page) {
                    var that = this;
                    var data = {
                        page: page,
                        form: this.form
                    };
                    $.ajax({
                        type: 'GET',
                        url: '/admin/templates/lists',
                        data: data,
                        success: function (response) {
                            that.data = response.data;
                            that.total = response.count;
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

                create: function () {
                    window.location.href = "/admin/templates/create";
                },
                handleShow(row) {
                    $.ajax({
                        type: 'GET',
                        url: '/admin/templates/' + row.id,
                        success: function (response) {
                            // window.location.href = response.msg;
                            window.open( response.msg)
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
                handleEdit(index, row) {
                     window.location.href = "/admin/templates/" + row.id + '/edit';
                },
                handleDelete(index, row) {
                    if (!confirm("是否删除")) {
                        return;
                    }

                    var that = this;
                    var data = {
                        page: row,
                    };
                    $.ajax({
                        type: 'POST',
                        method: 'DELETE',
                        url: '/admin/templates/' + row.id,
                        data: data,
                        success: function (response) {
                            if (response.result == true) {
                                that.data.splice(index, 1);
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
            },
            mounted: function () {
                this.init(1);
            },
            computed: {}
        });


    </script>
@endsection
