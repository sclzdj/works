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
                            {{--                            <el-date-picker--}}
                            {{--                                v-model="form.created_at"--}}
                            {{--                                type="daterange"--}}
                            {{--                                range-separator="至"--}}
                            {{--                                value-format="yyyy-MM-dd"--}}
                            {{--                                start-placeholder="开始日期"--}}
                            {{--                                end-placeholder="结束日期">--}}
                            {{--                            </el-date-picker>--}}

                            <el-select style="width: 150px" v-model="form.remark2" placeholder="运营">
                                <el-option
                                    v-for="(item,index) in remark2Option"
                                    :key="index"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.remark3" placeholder="类别">
                                <el-option
                                    v-for="(item,index) in remark3Option"
                                    :key="index"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.status" placeholder="验证码状态">
                                <el-option
                                    v-for="item in statusOption"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.orderBy" placeholder="排序">

                                <el-option key="1" value="photographer_works_count" label="项目数"></el-option>
                                <el-option key="2" value="photographer_works_resource_count" label="作品数"></el-option>
                            </el-select>

                            <el-input v-model="form.remark" style="width: 200px" placeholder="关键词"></el-input>


                            <el-button type="primary" @click="search" icon="el-icon-search">搜索</el-button>
                            <el-button type="primary" @click="clear" icon="el-icon-close">清除</el-button>

                            {{--                            <el-button type="primary" @click="create" icon="el-icon-search">创建</el-button>--}}
                            <br/>

                        </div>
                        <div class="block-content">
                            <el-table
                                :data="data"
                                style="width: 100%"
                                @selection-change="handleSelectionChange"
                            >
                                {{--                                <el-table-column--}}
                                {{--                                    type="selection"--}}
                                {{--                                    width="55">--}}
                                {{--                                </el-table-column>--}}
                                <el-table-column label="运营">
                                    <template slot-scope="scope">

                                        <el-select @change="((val)=>{changeStatus(scope.row,'remark2')})"
                                                   style="width: 150px" v-model="scope.row.remark2"
                                                   placeholder="请选择">
                                            <el-option
                                                v-for="item in remark2Option"

                                                :label="item.label"
                                                :value="item.value">
                                            </el-option>
                                        </el-select>
                                    </template>
                                </el-table-column>

                                <el-table-column label="类别">
                                    <template slot-scope="scope">

                                        <el-select @change="((val)=>{changeStatus(scope.row,'remark3')})"
                                                   style="width: 150px" v-model="scope.row.remark3"
                                                   placeholder="请选择">
                                            <el-option
                                                v-for="item in remark3Option"

                                                :label="item.label"
                                                :value="item.value">
                                            </el-option>
                                        </el-select>
                                    </template>
                                </el-table-column>

                                <el-table-column label="项目数据">
                                    <template slot-scope="scope">
                                        项目数:<span v-text="scope.row.photographer_works_count"></span><br/>
                                        作品数:<span v-text="scope.row.photographer_works_resource_count"></span>
                                    </template>
                                </el-table-column>

                                <el-table-column
                                    prop="status"
                                    label="状态"
                                    width="110px"
                                    min-width="110px"
                                >
                                </el-table-column>

                                <el-table-column
                                    prop="code"
                                    label="创建码"
                                    width="110px"
                                    min-width="110px"
                                >
                                </el-table-column>

                                <el-table-column
                                    prop="remark"
                                    label="备注名">

                                    <template slot-scope="scope">

                                        <div v-if="scope.row.status == '已创建'">
                                            <a target="_blank"
                                               v-bind:href="'/admin/works/photographerWork?photographer_id='+scope.row.photographer_id"><span
                                                    v-text="scope.row.nickname"></span></a>
                                        </div>

                                        <div v-else>
                                            <span v-text="scope.row.nickname"></span>
                                        </div>

                                        <el-input style="width: 150px" @blur="updateRemark(scope.row)"
                                                  v-model="scope.row.remark" placeholder="请输入内容"></el-input>

                                    </template>

                                </el-table-column>


                                {{--                                <el-table-column--}}
                                {{--                                    prop="created_at"--}}
                                {{--                                    label="创建时间">--}}
                                {{--                                </el-table-column>--}}

                                <el-table-column fixed="right" label="操作" width="100">
                                    <template slot-scope="scope">
                                        <el-button @click="handleDelete(scope.$index, scope.row)" type="text"
                                                   size="small">删除
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
                    created_at: [],
                    remark: "",
                    remark2: "",
                    remark3: "",
                    orderBy: "",
                },
                typeOption: [
                    {
                        value: 0,
                        label: '选择类型'
                    },
                    {
                        value: 1,
                        label: '用户创建'
                    }, {
                        value: 2,
                        label: '后台创建'
                    }],
                statusOption: [
                    {
                        value: -1,
                        label: '选择状态'
                    },
                    {
                        value: 0,
                        label: '已生成'
                    }, {
                        value: 1,
                        label: '已绑定'
                    }, {
                        value: 2,
                        label: '已校验'
                    },
                    {
                        value: 4,
                        label: '已创建'
                    }

                ],
                remark2Option: [

                    {
                        value: "谢莉莉",
                        label: '谢莉莉'
                    },
                    {
                        value: "周燕云",
                        label: '周燕云'
                    },
                    {
                        value: "王丫丫",
                        label: '王丫丫'
                    },
                    {
                        value: "小助理",
                        label: '小助理'
                    },
                    {
                        value: "谢莉莉",
                        label: '谢莉莉'
                    },
                    {
                        value: "周星宜",
                        label: '周星宜'
                    },
                    {
                        value: "王一坤",
                        label: '王一坤'
                    },
                ],
                remark3Option: [

                    {
                        value: "敢死队",
                        label: '敢死队'
                    },
                    {
                        value: "随食拍",
                        label: '随食拍'
                    },
                    {
                        value: "媒体",
                        label: '媒体'
                    },
                    {
                        value: "大咖",
                        label: '大咖'
                    },
                    {
                        value: "KOL",
                        label: 'KOL'
                    },
                    {
                        value: "裂变",
                        label: '裂变'
                    }
                ],
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
                        url: '/admin/invite/lists',
                        data: data,
                        success: function (response) {
                            that.data = response.data;
                            that.total = response.count;
                            for (let i = 0; i < that.data.length; i++) {
                                switch (that.data[i].status) {
                                    case 0:
                                        that.data[i].status = "已生成";
                                        break;
                                    case 1:
                                        that.data[i].status = "已绑定";
                                        break;
                                    case 2:
                                        that.data[i].status = "已校验";
                                        break;
                                    case 4:
                                        that.data[i].status = "已创建";
                                        break;
                                    default:
                                        break;
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
                        is_send: -1,
                        remark: "",
                        remark2: "",
                        remark3: "",
                        orderBy: "",
                    };
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                search: function () {
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                create: function () {
                    var that = this;
                    $.ajax({
                        type: 'POST',
                        url: '/admin/invite',
                        data: {
                            action: 'create'
                        },
                        success: function (response) {
                            if (response.result) {
                                that.init(1);
                                that.number = 0;
                            } else {
                                alert(response.msg);
                            }
                        },
                        error: function (xhr, status, error) {

                        }
                    });
                },
                upadteStatus: function (data) {
                    $.ajax({
                        type: 'POST',
                        url: '/admin/invotecode',
                        data: {
                            id: data.id,
                            action: 'update'
                        },
                        success: function (response) {
                            if (response.result) {
                                data.status = "已占用";
                            }
                        }
                    });
                },
                changeStatus(data, type) {

                    $.ajax({
                        type: 'PUT',
                        url: '/admin/invite/' + data.id,
                        data: {
                            data: data,
                            action: type
                        },
                        success: function (response) {
                            if (response.result) {

                            }
                        }
                    });
                },
                handleSelectionChange(val) {
                    this.multipleSelection = val;
                },
                updateRemark: function (data) {
                    $.ajax({
                        type: 'PUT',
                        url: '/admin/invite/' + data.id,
                        data: {
                            data: data,
                            action: 'remark'
                        },
                        success: function (response) {
                            if (response.result) {

                            }
                        }
                    });
                },
                send() {
                    alert('发送中，请等待');
                    var that = this;
                    $.ajax({
                        type: 'POST',
                        url: '/admin/invotecode',
                        data: {
                            action: 'send'
                        },
                        success: function (response) {
                            if (response.result) {
                                alert('发送完成');
                                that.init(1);
                            }
                        }
                    });

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
                        url: '/admin/invite/' + row.id,
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
