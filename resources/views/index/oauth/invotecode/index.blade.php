<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="viewport" id="viewportMeta">
    <title></title>

    <script src="https://res.wx.qq.com/open/js/jweixin-1.4.0.js" type="text/javascript" charset="utf-8"></script>

    <script src="{{asset('/node_modules/amfe-flexible/index.js')}}"></script>

    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

    <style type="text/css">
        body {
            margin: 0;
            background: #25a6ca;
            text-align: center;
        }

        #content {
            text-align: center;
            width: 10rem;
            position: relative;
        }

        input {
            width: 7.5rem;
            /* height: 1.466666rem; */
            /* width: 300px; */
            height: 56px;
            background: rgba(255, 255, 255, 1);
            border-radius: 9.333333rem;
            /* font-size:0.5rem; */
            font-size: 20px;
            font-family: PingFang SC;
            font-weight: bold;
            color: rgba(50, 50, 50, 1);
            border: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            outline: none;
            letter-spacing: 10px;
            text-indent: 10px;
            border-radius: 9.333333rem;
            margin: auto;
            margin-top: 1rem;
        }

        /* input::first-line {
		  color: rgba(50,50,50,1);
		}
 */

        input::-webkit-input-placeholder {

            /* Chrome/Opera/Safari */

            /* font-size: 0.5rem; */
            font-size: 20px;

            font-family: PingFang SC;

            font-weight: 400;

            color: rgba(200, 200, 200, 1) !important;

        }



        input::-moz-placeholder {

            /* Firefox 19+ */

            /* font-size: 0.5rem; */
            font-size: 20px;

            font-family: PingFang SC;

            font-weight: 500;

            color: rgba(200, 200, 200, 1);

        }



        input:-ms-input-placeholder {

            /* IE 10+ */

            /* font-size: 0.5rem; */
            font-size: 20px;

            font-family: PingFang SC;

            font-weight: 500;

            color: rgba(200, 200, 200, 1);

        }



        input:-moz-placeholder {

            /* Firefox 18- */

            /* font-size: 0.5rem; */
            font-size: 20px;

            font-family: PingFang SC;

            font-weight: 500;

            color: rgba(200, 200, 200, 1);

        }
    </style>

    <script type="text/javascript" charset="utf-8">
        wx.config(<?php echo $app->jssdk->buildConfig(array('checkJSApi'), false) ?>);
    </script>



    <script type="application/javascript">
        // window.onload = function() {

        //     document.getElementById("content").setAttribute("style", 'min-height:' + (window.screen.height*2-128)/75 + 'rem');

        //     document.getElementById("topContent").setAttribute("style", 'height:' + ((window.screen.height*2)-724-128)/75 + 'rem;'+'display: flex;justify-content: center;align-items: center;flex-direction: column;');

        // }

        var initViewport = function(height) {
            var metaEl = document.querySelector("#viewportMeta");
            var content = "height=" + height + ",width=device-width,initial-scale=1.0,user-scalable=no";
            metaEl.setAttribute('name', 'viewport');
            metaEl.setAttribute('content', content);
        }
        var realHeight = window.innerWidth > window.innerHeight ? window.innerWidth : window.innerHeight
        initViewport(realHeight);
    </script>

</head>

<body>

    <div id="content" style="height: 100vh;position: fixed;top: 0;background:#25a6ca">

        <div id="topContent" style="height: 45vh;display: flex;justify-content: center;align-items: center;">
            <div style="margin-top: -0.33333rem;">
                <embed src="{{asset('images/title.svg')}}" style="margin: auto;width: 4rem;height: 1.493333rem;" />

                <input class="input" type="text" v-model="inputData" :placeholder="inputPlace" id="inputs" maxlength="6" style="text-transform:uppercase;caret-color: #323232;-webkit-appearance: none;appearance: none;" @focus="inputfocus" @blur="inputblur" @input="inputWrite" adjust-position="false" />

                <div style="width: 10rem;height: 14px;margin: auto;margin-top: 0.533333rem;display: flex;justify-content: center;">
                    <div class="" style="font-size:14px;font-family:PingFang SC;font-weight:500;color:rgba(255,255,255,1);height: 0.4rem;line-height: 0.4rem;margin-top: 0.013333rem;user-select:none;-ms-user-select:none;-moz-user-select:none;-webkit-user-select:none;" @click="redirectTo">申请参加内测</div>
                </div>
            </div>
        </div>



        <img src="{{asset('images/888.jpg')}}" style="margin: auto;width: 100vw;height: 55vh;" />

        <div style="width: 100vw;height: 100vh;display: flex;justify-content: center;align-items: center;position: fixed;top:0px;" v-if="showTest">
            <div style="background:rgba(0,0,0,0.7);color: white;font-size: 0.346666rem;padding: 0.266666rem;border-radius: 0.133333rem;font-weight: 500;position:absolute;top:40vh;">
                @{{content}}
            </div>
        </div>

    </div>

    <script src="{{asset('/static/admin/js/core/jquery.min.js')}}"></script>



    <script>
        new Vue({

            el: '#content',

            data: {

                inputData: "",
                showTest: false,
                content: '',
                inputPlace: '请输入创建码'
            },

            methods: {

                redirectTo: function() {
                    var that = this
                    that.showTest = true
                    that.content = '我们会尽快联系你'
                    setTimeout(function() {
                        that.showTest = false
                    }, 1000)

                    var data = {
                  
                       'user_id': '{{$data['userId']}}',

                       'source' : 2

                    };

                    $.ajax({
                        type:'POST',
                        url: '/api/target/insert',
                        data: data,
                        success:function(response){
                           console.log(response,'response')
                        }
                    })

                    wx.miniProgram.redirectTo({url: "/subPage/createPrivate/createPrivate"})



                },
                sureFn() {
                    this.showTest = false
                },
                inputfocus() {
                    this.inputPlace = ''
                },
                inputblur() {
                    this.inputPlace = '请输入创建码'
                },
                inputWrite: function() {

                    if (this.inputData.length === 6) {

                        var data = {

                            'code': this.inputData,

                            'userid': '{{$data['userId']}}'

                        };
                        var that = this
                        console.log(data);

                        $.ajax({

                            type: 'POST',

                            url: '/api/invote/query',

                            data: data,

                            success: function(response) {

                                if (response.data.result == true) {

                                    wx.miniProgram.redirectTo({url: "/pages/registGuid/index"})

                                } else {

                                    that.showTest = true
                                    that.content = response.data.msg
                                    setTimeout(function() {
                                        that.showTest = false
                                    }, 1000)
                                }

                            },

                            error: function(xhr, status, error) {

                                var response = JSON.parse(xhr.responseText);

                                if (xhr.status == 419) { // csrf错误，错误码固定为419

                                    that.showTest = true
                                    that.content = '请勿重复请求~'
                                    setTimeout(function() {
                                        that.showTest = false
                                    }, 1000)

                                } else if (xhr.status == 422) { // 验证错误

                                    var message = [];

                                    for (var i in response.errors) {

                                        message = message.concat(response.errors[i]);

                                    }

                                    message = message.join(',');

                                    that.showTest = true
                                    that.content = message
                                    setTimeout(function() {
                                        that.showTest = false
                                    }, 1000)
                                } else {

                                    if (response.message) {


                                        that.showTest = true
                                        that.content = response.message
                                        setTimeout(function() {
                                            that.showTest = false
                                        }, 1000)

                                    } else {

                                        that.showTest = true
                                        that.content = '服务器错误~'
                                        setTimeout(function() {
                                            that.showTest = false
                                        }, 1000)

                                    }

                                }

                            }

                        });

                    }

                }

            }

        })
    </script>

</html>