@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
@extends('admin.layouts.master')

@section('pre_css')
    <!-- 引入样式 -->
    <link rel="stylesheet" href="{{asset('/static/admin/css/element.css').'?'.$SFV}}">

@endsection

<style>

    .boxrow {
        display: inline-block;
        width: 100%;
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

</style>

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
                    <h3 class="block-title">问题反馈</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">

                            <el-row :gutter="10">
                                <el-col :span="5">

                                    <el-date-picker style="width: 100%"
                                                    v-model="form.created_at"
                                                    type="daterange"
                                                    range-separator="至"
                                                    value-format="yyyy-MM-dd"
                                                    start-placeholder="开始日期"
                                                    end-placeholder="结束日期">
                                    </el-date-picker>
                                </el-col>
                                <el-col :span="5">
                                    <el-input v-model="form.keyword" placeholder="从描述中搜索"></el-input>
                                </el-col>

                            </el-row>

                            <el-row :gutter="10">
                                <el-col :span="3">
                                    <el-select style="width: 100%" v-model="form.type" placeholder="请选择">
                                        <el-option
                                                v-for="item in typeOption"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value">
                                        </el-option>
                                    </el-select>
                                </el-col>
                                <el-col :span="3">
                                    <el-select style="width: 100%" v-model="form.page" placeholder="请选择">
                                        <el-option
                                                v-for="item in pages"
                                                :key="item"
                                                :label="item"
                                                :value="item">
                                        </el-option>
                                    </el-select>
                                </el-col>
                                <el-col :span="4">
                                    <el-select style="width: 100%" v-model="form.status" placeholder="请选择">
                                        <el-option
                                                v-for="item in statusOption"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value">
                                        </el-option>
                                    </el-select>
                                </el-col>
                            </el-row>




                            <el-button type="primary" @click="search" icon="el-icon-search">搜索</el-button>
                            <el-button type="primary" @click="clear" icon="el-icon-close">清除</el-button>
                            <el-button type="primary" @click="exports" icon="el-icon-close">导出</el-button>


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

                                            <el-form-item label="图片:">
                                                <div class="demo-image__preview" v-if="props.row.img.length > 0">
                                                    <el-image
                                                            style="width: 100px; height: 100px"
                                                            :src="props.row.img[0]"
                                                            :preview-src-list="props.row.img">
                                                    </el-image>
                                                </div>
                                            </el-form-item>

                                            <el-form-item label="视频:" class="boxrow">
                                               <span v-if="props.row.video.length > 0" v-for="video in props.row.video">
                                                <a target="_blank" :href="video">查看视频</a>
                                               </span>
                                            </el-form-item>

                                            <el-form-item label="手机版本:" class="boxrow">
                                                <span>  @{{ props.row.mobile_version }}</span>
                                            </el-form-item>


                                            <el-form-item label="系统版本:" class="boxrow">
                                                <span>  @{{ props.row.system_version }}</span>
                                            </el-form-item>

                                            <el-form-item label="微信版本:" class="boxrow">
                                                <span>  @{{ props.row.wechat_version }}</span>
                                            </el-form-item>

                                            <el-form-item label="语言:" class="boxrow">
                                                <span>  @{{ props.row.language }}</span>
                                            </el-form-item>

                                        </el-form>
                                    </template>
                                </el-table-column>

                                <el-table-column
                                        type="selection"
                                        width="55">
                                </el-table-column>

                                <el-table-column
                                        label="状态"
                                        width="150">
                                    <template slot-scope="scope">
                                        <el-select @change="changeStatus(scope.row)" v-model="scope.row.status"
                                                   placeholder="请选择">
                                            <el-option
                                                    v-for="item in status2Option"
                                                    :key="item.value"
                                                    :label="item.label"
                                                    :value="item.value">
                                            </el-option>
                                        </el-select>
                                    </template>
                                </el-table-column>


                                <el-table-column
                                        prop="diffNowTime"
                                        label="全时" width="100">
                                </el-table-column>

                                <el-table-column
                                        prop="diffEditTime"
                                        label="现时" width="100">
                                </el-table-column>

                                <el-table-column label="描述">


                                    <template slot-scope="scope">
                                        <el-tooltip placement="top">
                                            <div slot="content">
                                                鼠标离开边框自动提交
                                            </div>
                                            <el-input
                                                    maxlength="255"
                                                    type="textarea"
                                                    :autosize="{ minRows: 2, maxRows: 8}"
                                                    placeholder="请输入内容"
                                                    v-model="scope.row.content"
                                                    @blur="changeStatus(scope.row)"
                                            >
                                            </el-input>
                                        </el-tooltip>
                                    </template>
                                </el-table-column>


                                <el-table-column
                                        prop="nickname"
                                        label="用户">
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
                    pages: 0,
                    video: [],
                    img: []
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
                    page: "选择页面",
                    multipleSelection: [],
                    keyword: ""
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
                        label: '待沟通'
                    }, {
                        value: 1,
                        label: '待认领'
                    }, {
                        value: 2,
                        label: '处理中'
                    },
                    {
                        value: 3,
                        label: '已处理'
                    }
                    , {
                        value: 4,
                        label: '已归档'
                    }
                    , {
                        value: 5,
                        label: '被合并'
                    }
                    , {
                        value: 6,
                        label: '被搁置'
                    }
                ],
                status2Option: [],
                pages: [
                    '选择页面',
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
                                that.data[i].img = [];
                                that.data[i].video = [];
                                if (that.data[i].attachment) {
                                    that.data[i].attachment = JSON.parse(that.data[i].attachment)
                                    for (let j = 0; j < that.data[i].attachment.length; j++) {
                                        switch (that.data[i].attachment[j].type) {
                                            case "img":
                                                that.data[i].img.push(that.data[i].attachment[j].value);
                                                break;
                                            case "video":
                                                that.data[i].video.push(that.data[i].attachment[j].value);
                                                break;
                                        }
                                    }

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
                    let status = this.statusOption.slice();
                    status.shift();
                    this.status2Option = status;
                },
                clear: function () {
                    this.form = {
                        type: 0,
                        status: -1,
                        created_at: [],
                        page: "选择页面",
                        keyword: ""
                    };
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                search: function () {
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                changeStatus: function (dataItem) {
                    var that = this;
                    var formData = {
                        form: dataItem
                    };
                    $.ajax({
                        url: '/admin/question',
                        method: 'post',
                        data: formData,
                        success: function (response) {
                            dataItem.diffEditTime = "0天0小时0分钟";
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
                handleSelectionChange(rows) {
                    this.form.multipleSelection = [];
                    rows.forEach(row => {
                        this.form.multipleSelection.push(row.id);
                    });
                },
                exports: function () {
                    window.location.href = "/admin/question/export?params=" + JSON.stringify(this.form);
                },

            },
            mounted: function () {
                this.init(1);
            },
            computed: {}
        });


    </script>
@endsection
