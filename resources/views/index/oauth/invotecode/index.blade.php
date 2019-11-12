<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <title></title>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.4.0.js" type="text/javascript" charset="utf-8"></script>
    <script src="{{asset('/node_modules/amfe-flexible/index.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <style type="text/css">
        body {
        	margin: 0;
        	background: #3ECDF6;
        	text-align: center;
        }
        #content{
        	text-align: center;width: 10rem;position: relative;
        }
        input {
            width: 6.666666rem;
            height: 1.466666rem;
            background: rgba(255, 255, 255, 1);
            border-radius: 9.333333rem;
            font-size:0.48rem;
            font-family:PingFang SC;
            font-weight:bold;
            color:rgba(50,50,50,1);
            border: 0;
            display: block;
            text-align: center;
            outline:none;
            letter-spacing:10px;
        	border-radius: 9.333333rem;
        	margin-top: 0.48rem;
        	line-height: 1.466666rem;
        }

        input::-webkit-input-placeholder {
            /* Chrome/Opera/Safari */
            font-size: 0.48rem;
            font-family: PingFang SC;
            font-weight: 400;
            color: rgba(200, 200, 200, 1);
        }

        input::-moz-placeholder {
            /* Firefox 19+ */
            font-size: 0.48rem;
            font-family: PingFang SC;
            font-weight: 500;
            color: rgba(200, 200, 200, 1);
        }

        input:-ms-input-placeholder {
            /* IE 10+ */
            font-size: 0.48rem;
            font-family: PingFang SC;
            font-weight: 500;
            color: rgba(200, 200, 200, 1);
        }

        input:-moz-placeholder {
            /* Firefox 18- */
            font-size: 0.48rem;
            font-family: PingFang SC;
            font-weight: 500;
            color: rgba(200, 200, 200, 1);
        }
    </style>
    <script type="text/javascript" charset="utf-8">
        wx.config(<?php echo $app->jssdk->buildConfig(array('checkJSApi'), false) ?>);
    </script>

    <script type="application/javascript">
        window.onload = function() {
            document.getElementById("content").setAttribute("style", 'min-height:' + (window.screen.height*2-128)/75 + 'rem');
            document.getElementById("topContent").setAttribute("style", 'height:' + ((window.screen.height*2)-724-128)/75 + 'rem;'+'display: flex;justify-content: center;align-items: center;flex-direction: column;');
        }
    </script>
</head>
<body>
<div id="content">
    <div id="topContent">
		<img src="{{asset('images/titles.png')}}" style="margin: auto;width: 4rem;height: 1.48rem;" />
		<input class="input" type="text" v-model="inputData" placeholder="请输入邀请码" id="inputs" maxlength="6" style="text-transform:uppercase" @input="inputWrite"/>
		<div style="width: 10rem;height: 0.4rem;margin: auto;margin-top: 0.533333rem;display: flex;justify-content: center;" @click="redirectTo">
		    <div class="" style="text-align: center;line-height: 0.4rem;width:0.4rem;height:0.4rem;background:rgba(255,255,255,1);border-radius:50%;font-size:0.2666rem;font-family:PingFang SC;font-weight:500;color:rgba(62,205,246,1);">?</div>
		    <div class="" style="font-size:0.333333rem;font-family:PingFang SC;font-weight:500;color:rgba(255,255,255,1);margin-left: 0.23rem;margin-top: -0.026666rem;">怎样获得邀请码</div>
		</div>
	</div>
    <img src="{{asset('images/phone.png')}}" style="margin: auto;width: 6.013333rem;height: 9.653333rem;position: absolute;bottom: 0;left: 50%;margin-left: -3rem;" />
</div>
<script src="{{asset('/static/admin/js/core/jquery.min.js')}}"></script>

<script>
    new Vue({
        el: '#content',
        data: {
            inputData:"",
        },
        methods: {
            redirectTo: function () {
                wx.miniProgram.navigateTo({url: "/subPage/crouwdUp/crouwdUp"})
            },
            inputWrite:function(){
                if (this.inputData.length === 6) {
                    var data = {
                        'code': this.inputData,
                        'userid': '{{$data['userId']}}'
                    };
                    console.log(data);
                    $.ajax({
                        type: 'POST',
                        url: '/api/invote/query',
                        data: data,
                        success: function (response) {
                            if (response.data.result == true) {
                                wx.miniProgram.redirectTo({url: "/pages/login2/login2"})
                            } else {
                                alert(response.data.msg);
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
                }
            }
        }
    })
</script>
</html>
