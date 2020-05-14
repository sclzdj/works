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
                    <h3 class="block-title">邀请管理</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <el-date-picker
                                v-model="form.created_at"
                                type="daterange"
                                range-separator="至"
                                value-format="yyyy-MM-dd"
                                start-placeholder="开始日期"
                                end-placeholder="结束日期">
                            </el-date-picker>

                            <el-select style="width: 150px" v-model="form.type" placeholder="请选择">
                                <el-option
                                    v-for="item in typeOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.status" placeholder="请选择">
                                <el-option
                                    v-for="item in statusOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>


                            <el-button type="primary" @click="search" icon="el-icon-search">搜索</el-button>
                            <el-button type="primary" @click="clear" icon="el-icon-close">清除</el-button>


                        </div>
                        <div class="block-content">
                            <el-table
                                :data="data"
                                style="width: 100%"
                                @selection-change="handleSelectionChange"
                            >


                                <el-table-column type="expand">
                                    <template slot-scope="props">
                                        <el-form label-position="left" inline class="demo-table-expand">
                                            <el-form-item label="意见:">
                                                <span>  @{{ props.row.content }}</span>
                                            </el-form-item>
                                            <br/>
                                            <el-form-item label="附件:">
                                                <div class="demo-image__preview" v-if="props.row.attachment">
                                                    <el-image
                                                        style="width: 100px; height: 100px"
                                                        :src="props.row.attachment[0]"
                                                        :preview-src-list="props.row.attachment">
                                                    </el-image>
                                                </div>
                                            </el-form-item>
                                        </el-form>
                                    </template>
                                </el-table-column>

                                <el-table-column
                                    type="selection"
                                    width="55">
                                </el-table-column>

                                <el-table-column
                                    prop="page"
                                    label="页面"
                                    width="180">
                                </el-table-column>

                                <el-table-column
                                    prop="type"
                                    label="类型"
                                    width="180">
                                </el-table-column>

                                <el-table-column
                                    prop="status"
                                    label="状态">
                                    <template slot-scope="scope">
                                        <span v-if="scope.row.status == 0">未处理</span>
                                        <span v-if="scope.row.status == 1">待处理</span>
                                        <span v-if="scope.row.status == 2">已完结</span>

                                    </template>
                                </el-table-column>

                                <el-table-column
                                    prop="nickname"
                                    label="用户">
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
                                        <el-select @change="changeStatus(scope.row)" v-model="scope.row.status" placeholder="请选择">
                                            <el-option
                                                v-for="item in status2Option"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value">
                                            </el-option>
                                        </el-select>
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
                typeOption: [
                    {
                        value: 0,
                        label: '选择类型'
                    },
                    {
                        value: 1,
                        label: 'bug'
                    }, {
                        value: 2,
                        label: '建议'
                    }],
                statusOption: [
                    {
                        value: -1,
                        label: '选择状态'
                    },
                    {
                        value: 0,
                        label: '未处理'
                    }, {
                        value: 1,
                        label: '待处理'
                    }, {
                        value: 2,
                        label: '已完结'
                    }],
                status2Option: [
                    {
                        value: 0,
                        label: '未处理'
                    }, {
                        value: 1,
                        label: '待处理'
                    }, {
                        value: 2,
                        label: '已完结'
                    }],
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
                        url: '/admin/question/lists',
                        data: data,
                        success: function (response) {
                            that.data = response.data;
                            that.total = response.count;
                            for (let i = 0; i < that.data.length; i++) {
                                switch (that.data[i].type) {
                                    case 1:
                                        that.data[i].type = "bug";
                                        break;
                                    case 2:
                                        that.data[i].type = "建议";
                                        break;
                                    default:
                                        break;
                                }
                                if (that.data[i].attachment) {
                                    that.data[i].attachment = JSON.parse(that.data[i].attachment)
                                }
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
                clear: function () {
                    this.form = {
                        type: 0,
                        status: -1,
                        created_at: [],
                    };
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                search: function () {
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                changeStatus: function (data) {
                    var that = this;
                    var formData = {
                        form: data
                    };
                    $.ajax({
                        url: '/admin/question',
                        method: 'post',
                        data: formData,
                        success: function (response) {

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
                handleSelectionChange(val) {
                    this.multipleSelection = val;
                },

            },
            mounted: function () {
                this.init(1);
            },
            computed: {}
        });


    </script>
@endsection
