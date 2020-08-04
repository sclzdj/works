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

        .boxrow {
            display: inline-block;
            width: 100%;
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
                    <h3 class="block-title">目标用户管理</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">

                            <el-select style="width: 150px" v-model="form.sources" placeholder="来源">
                                <el-option
                                    v-for="item in sources"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.codeStatus" placeholder="选择状态">
                                <el-option
                                    v-for="item in status"
                                    :key="item.value"
                                    :label="item.label"
                                    :value="item.value">
                                </el-option>
                            </el-select>

                            <el-select style="width: 150px" v-model="form.status" placeholder="选择推送">
                                <el-option label="选择推送" :value="-1">选择推送</el-option>
                                <el-option label="推送" :value="1">推送</el-option>
                                <el-option label="未推送" :value="2">未推送</el-option>
                                {{--                                <el-option--}}
                                {{--                                        v-for="item in status"--}}
                                {{--                                        :key="item.value"--}}
                                {{--                                        :label="item.label"--}}
                                {{--                                        :value="item.value">--}}
                                {{--                                </el-option>--}}
                            </el-select>

                            <el-input v-model="form.phone" style="width: 200px" placeholder="电话搜索"></el-input>

                            <el-button type="primary" @click="search" icon="el-icon-search">搜索</el-button>
                            <el-button type="primary" @click="clear" icon="el-icon-close">清除</el-button>

                        </div>
                        <div class="block-content">
                            <el-table
                                :data="data"
                                style="width: 100%"
                            >
                                <el-table-column type="expand">
                                    <template slot-scope="props">
                                        <el-form label-position="left" inline class="demo-table-expand">


                                            <el-form-item label="来源" class="boxrow">
                                                <span>@{{ props.row.source }}</span>
                                            </el-form-item>

                                            <el-form-item label="头衔" class="boxrow">
                                                <span>@{{ props.row.rank_name }}</span>
                                            </el-form-item>

                                            <el-form-item label="理由" class="boxrow">
                                                <span>@{{ props.row.reason }}</span>
                                            </el-form-item>

                                            <el-form-item label="作品图片:" class="boxrow">
                                                <div class="demo-image__preview" v-if="props.row.works_info">
                                                    <el-image
                                                        style="width: 100px; height: 100px"
                                                        :src="props.row.works_info[0]"
                                                        :preview-src-list="props.row.works_info">
                                                    </el-image>
                                                </div>
                                            </el-form-item>


                                            {{--                                            <el-form-item label="地址" class="boxrow">--}}
                                            {{--                                                <span>@{{ props.row.address }}</span>--}}
                                            {{--                                            </el-form-item>--}}

                                            {{--                                            <el-form-item label="城市" class="boxrow">--}}
                                            {{--                                                <span>@{{ props.row.city }}</span>--}}
                                            {{--                                            </el-form-item>--}}


                                            {{--                                            <el-form-item label="性别" class="boxrow">--}}
                                            {{--                                                <span>@{{ props.row.gender }}</span>--}}
                                            {{--                                            </el-form-item>--}}



                                            {{--                                            <el-form-item label="微信号" class="boxrow">--}}
                                            {{--                                                <span>@{{ props.row.wechat }}</span>--}}
                                            {{--                                            </el-form-item>--}}


                                        </el-form>
                                    </template>
                                </el-table-column>


                                <el-table-column
                                    prop="user_id"
                                    label="用户id">
                                </el-table-column>

                                {{--                                <el-table-column--}}
                                {{--                                    type="selection"--}}
                                {{--                                >--}}
                                {{--                                </el-table-column>--}}





                                {{--                                <el-table-column--}}
                                {{--                                        label="状态"--}}
                                {{--                                        >--}}
                                {{--                                    <template slot-scope="scope">--}}
                                {{--                                        <el-select @change="changeStatus(scope.row)" v-model="scope.row.status"--}}
                                {{--                                                   placeholder="请选择">--}}
                                {{--                                            <el-option--}}
                                {{--                                                    v-for="item in status"--}}
                                {{--                                                    :key="item.value"--}}
                                {{--                                                    :label="item.label"--}}
                                {{--                                                    :value="item.value">--}}
                                {{--                                            </el-option>--}}
                                {{--                                        </el-select>--}}
                                {{--                                    </template>--}}
                                {{--                                </el-table-column>--}}

                                <el-table-column label="姓名">
                                    <template slot-scope="scope">
                                        <div v-if="scope.row.invote_status == '已创建'">
                                            <a target="_blank"
                                               v-bind:href="'/admin/works/photographerWork?photographer_id='+scope.row.photographer_id">
                                                <span v-text="scope.row.nickname"></span><br/>
                                                <span style="color:#0f8496;font-size: 12px"
                                                      v-text="scope.row.last_name"></span>
                                            </a>
                                        </div>
                                        <div v-else>
                                            <span v-text="scope.row.nickname"></span><br/>
                                            <span style="color:#0f8496;font-size: 12px"
                                                  v-text="scope.row.last_name"></span>
                                        </div>

                                    </template>

                                </el-table-column>

                                <el-table-column
                                    prop="phoneNumber"
                                    label="手机号">
                                </el-table-column>

                                <el-table-column label="状态" prop="invote_status">

                                </el-table-column>


                                <el-table-column
                                    prop="code"
                                    label="邀请码">
                                </el-table-column>

                                <el-table-column
                                    prop="invote_type"
                                    label="邀请码的类型">
                                </el-table-column>


                                <el-table-column
                                    fixed="right"
                                    label="操作"
                                    width="100">
                                    <template slot-scope="scope">

                                        <el-button
                                            v-if="scope.row.status != 1 && scope.row.invote_status != '已创建' && scope.row.invote_status != '已校验' "
                                            @click="changeStatus(scope.$index, scope.row)" type="text"
                                            size="small">推送
                                        </el-button>
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
                    default: 20,
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
                        console.log(that.total, that.pageSize, Math.ceil(that.total / that.pageSize))
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
                    sources: -1,
                    status: -1,
                    codeStatus: -1,
                    multipleSelection: [],
                    phone: ""
                },
                sources: [
                    {
                        value: -1,
                        label: '选择来源'
                    },
                    {
                        value: 0,
                        label: '申请页提交'
                    }, {
                        value: 1,
                        label: '申请页点击'
                    },
                    {
                        value: 2,
                        label: '用户页点击'
                    }
                    , {
                        value: 5,
                        label: '创建页点击'
                    }
                ],
                status: [
                    {
                        value: -1,
                        label: '选择类型'
                    },
                    {
                        value: 0,
                        label: '已生成'
                    }, {
                        value: 1,
                        label: '已绑定'
                    },
                    {
                        value: 2,
                        label: '已校验'
                    },
                    {
                        value: 4,
                        label: '已创建'
                    },
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
                        url: '/admin/target/lists',
                        data: data,
                        success: function (response) {
                            that.data = response.data;
                            that.total = response.count;
                            for (let i = 0; i < that.data.length; i++) {
                                switch (that.data[i].source) {
                                    case 0:
                                        that.data[i].source = "申请页提交";
                                        break;
                                    case 1:
                                        that.data[i].source = "申请页点击";
                                        break;
                                    case 2:
                                        that.data[i].source = "用户页点击";
                                        break;
                                    case 5:
                                        that.data[i].source = "创建页点击";
                                        break;
                                    default:
                                        break;
                                }
                                switch (that.data[i].invote_status) {
                                    case 0:
                                        that.data[i].invote_status = "已生成";
                                        break;
                                    case 1:
                                        that.data[i].invote_status = "已绑定";
                                        break;
                                    case 2:
                                        that.data[i].invote_status = "已校验";
                                        break;
                                    case 4:
                                        that.data[i].invote_status = "已创建";
                                        break;
                                    default:
                                        break;
                                }
                                switch (that.data[i].gender) {
                                    case 0:
                                        that.data[i].gender = "未知";
                                        break;
                                    case 1:
                                        that.data[i].gender = "男";
                                        break;
                                    case 2:
                                        that.data[i].gender = "女";
                                        break;
                                    default:
                                        break;
                                }

                                //   that.data[i].nickname = that.data[i].last_name.length > 0 ? that.data[i].last_name + ' （' + that.data[i].nickname + '）': that.data[i].nickname

                                that.data[i].city = that.data[i].province + ' ' + that.data[i].city;
                                ;
                                if (that.data[i].works_info) {
                                    that.data[i].works_info = JSON.parse(that.data[i].works_info)
                                }
                            }
                        }
                        ,
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
                        sources: -1,
                        status: -1,
                        codeStatus: -1,
                        multipleSelection: [],
                        phone: ""
                    };
                    this.$refs.children.initPageNo();
                    this.init(1);
                },
                search: function () {
                    this.$refs.children.initPageNo();
                    this.init(1);
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
                        url: '/admin/target/' + row.id,
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
                changeStatus: function (index, row) {
                    var that = this;
                    var formData = {
                        form: row,
                        type: "createInvote"
                    };
                    $.ajax({
                        url: '/admin/target',
                        method: 'post',
                        data: formData,
                        success: function (response) {
                            if (response.result == true) {
                                row.invote_code_id = response.data.invote_code_id;
                                row.code = response.data.code;
                                row.invote_status = "已绑定";
                                row.status = 1;
                            } else {
                                that.$message.error(response.msg);
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
