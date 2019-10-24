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
                    <h3 class="block-title">众筹管理</h3>
                </div>
                <div class="tab-content" id="app">
                    <div class="tab-pane active">
                        <div class="block-content">
                            <form class="form-horizontal form-builder row" id="create-form">
                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line">真实数据</div>
                                    </div>
                                    <div class="col-md-4 form-control-static form-option-line">
                                        <div class="help-block help-block-line" style="text-align: center;">显示数据</div>
                                    </div>

                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        目标值
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.amount"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.amount" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <button type="button" class="button1" @click="update('amount' , 'reset')"> 重置
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        已筹人数
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.total"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">

                                        <el-button plain @click="update('total' , 'sub')">-</el-button>
                                        <el-input v-model.trim="form.total" type="number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button plain @click="update('total' , 'add')">+</el-button>

                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        已筹金额
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.total_price"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">

                                        <el-button plain @click="update('total_price' , 'sub')">-</el-button>
                                        <el-input v-model.trim="form.total_price" type="number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button plain @click="update('total_price' , 'add')">+</el-button>

                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        达成率
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="rate + '%'"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-button :disabled="true" plain>-</el-button>
                                        <el-input v-model.trim="input" :disabled="true" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button :disabled="true" plain>+</el-button>
                                    </div>
                                </div>


                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        99限制
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.limit_99"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.limit_99" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <button type="button" class="button1" @click="update('limit_99' , 'reset')">
                                            重置
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        99实购
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.data_99"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-button plain @click="update('data_99' , 'sub')">-</el-button>
                                        <el-input v-model.trim="form.data_99" type="number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button plain @click="update('data_99' , 'add')">+</el-button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        399限制
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.limit_399">

                                        </div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.limit_399" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>

                                        <button type="button" class="button1" @click="update('limit_399' , 'reset')">
                                            重置
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        399实购
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.data_399"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-button plain @click="update('data_399' , 'sub')">-</el-button>
                                        <el-input v-model.trim="form.data_399" type="number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button plain @click="update('data_399' , 'add')">+</el-button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        599限制
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.limit_599"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-input v-model.trim="form.limit_599" type="number"
                                                  placeholder="请输入内容" style='width: 150px'></el-input>

                                        <button type="button" class="button1" @click="update('limit_599' , 'reset')">
                                            重置
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <label class="col-md-2 control-label form-option-line">
                                        <span class="form-option-require"></span>
                                        599实购
                                    </label>
                                    <div class="col-md-2 form-control-static form-option-line">
                                        <div class="help-block help-block-line" v-text="crowdFunding.data_599"></div>
                                    </div>
                                    <div class="col-md-4 form-option-line" style="text-align: right;">
                                        <el-button plain @click="update('data_599' , 'sub')">-</el-button>
                                        <el-input v-model.trim="form.data_599" type="number" placeholder="请输入内容"
                                                  style='width: 150px'></el-input>
                                        <el-button plain @click="update('data_599' , 'add')">+</el-button>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/element-ui/lib/index.js"></script>

    <script>

        Vue.config.devtools = true;
        var vm = new Vue({
            el: '#app',
            data: {
                input: "",
                crowdFunding: {},
                form: {
                    data_99: 0,
                    data_399: 0,
                    data_599: 0,
                    limit_99: 0,
                    limit_399: 0,
                    limit_599: 0,
                    total: 0,
                    total_price: 0,
                    amount: 0,
                }
            },
            methods: {
                init: function () {
                    var that = this;
                    var data = {};
                    $.ajax({
                        type: 'GET',
                        url: '/admin/crowdfunding/lists',
                        data: data,
                        success: function (response) {
                            that.crowdFunding = response.crowdFunding;
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
                update: function (key, action) {
                    if (this.form[key] === 0) {
                        return;
                    }

                    if (this.form[key] === "") {
                        return;
                    }

                    var that = this;
                    var data = {
                        actions: action,
                        keys: key,
                        data: this.form[key]
                    };

                    $.ajax({
                        type: 'POST',
                        url: '/admin/crowdfunding',
                        data: data,
                        success: function (response) {
                            if (response.result) {
                                switch (action) {
                                    case "reset":
                                        that.crowdFunding[key] = data.data;
                                        break;
                                    case "add":
                                        that.crowdFunding[key] = Number(that.crowdFunding[key]) + Number(data.data);
                                        that.crowdFunding['total_price'] = Number(response.total_price);
                                        break;
                                    case "sub":
                                        that.crowdFunding[key] = Number(that.crowdFunding[key]) - Number(data.data);
                                        that.crowdFunding['total_price'] = Number(response.total_price);
                                        break;
                                }
                                that.form[key] = 0;
                            } else {

                                alert(response.msg);
                            }
                        },
                        error: function (xhr, status, error) {

                        }
                    });

                }
            },
            mounted: function () {
                this.init();
            },
            computed: {
                amount_price: function () {
                    return (Number(this.crowdFunding.data_99) * 99) + (Number(this.crowdFunding.data_399) * 399) + (Number(this.crowdFunding.data_599) * 599);
                },
                rate: function () {
                    return (Number(this.crowdFunding.total_price) / Number(this.crowdFunding.amount) * 100).toFixed(2);
                }
            }
        });


    </script>
@endsection
