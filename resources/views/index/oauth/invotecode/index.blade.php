<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title></title>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.4.0.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" charset="utf-8">
        wx.config(<?php echo $app->jssdk->buildConfig(array(), false) ?>);
    </script>
</head>
<body>
<div id="content" style="width: 10rem;position: relative;">
    <img src="{{asset('/img/titles.png')}}  " style="margin: auto;width: 4rem;height: 1.48rem;margin-top: 0.48rem;"/>
    <input  class="input" type="text" placeholder="请输入邀请码" id="inputs" maxlength="6"/>
    <div style="width: 2.413333rem;height: 0.4rem;margin: auto;margin-top: 0.38rem;">
        <div class=""
             style="text-align: center;line-height: 0.4rem;width:0.4rem;height:0.4rem;background:rgba(255,255,255,1);border-radius:50%;font-size:0.266666rem;font-family:PingFang SC;font-weight:500;color:rgba(62,205,246,1);float: left;">
            ?
        </div>
        <div class="" @click="redirectTo"
             style="font-size:0.266666rem;font-family:PingFang SC;font-weight:500;color:rgba(255,255,255,1);width: 2.013333rem;float: left;">
            怎样获得邀请码
        </div>
    </div>
    <img src="{{asset('/img/phone.png')}}"
         style="margin: auto;width: 6.013333rem;height: 9.653333rem;position: absolute;bottom: 0;left: 2rem;"/>
</div>
</body>
<style type="text/css">
    body {
        margin: 0;
        background: #3ECDF6;
        text-align: center;
    }

    input{
        width: 6.666666rem;
        height: 1.466666rem;
        background: rgba(255, 255, 255, 1);
        border-radius: 700px;
        margin: auto;
        margin-top: 0.48rem;
        line-height: 1.466666rem;
    }

    input {
        width: 6.666666rem;
        height: 1.466666rem;
        background: rgba(255, 255, 255, 1);
        border-radius: 700px;
        font-size:0.64rem;
        font-family:PingFang SC;
        font-weight:bold;
        color:rgba(50,50,50,1);
        border: 0;
        display: block;
        text-align: center;
        outline:none;
        letter-spacing:10px;
    }





    input::-webkit-input-placeholder {
        /* Chrome/Opera/Safari */
        font-size: 0.48rem;
        font-family: PingFang SC;
        font-weight: 500;
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
<script src="{{asset('/static/admin/js/core/jquery.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script type="application/javascript">
    window.onload = function () {
        var scale = 1 / devicePixelRatio;
        document.querySelector('meta[name="viewport"]').setAttribute('content', 'initial-scale=' + scale +
            ', maximum-scale=' + scale + ', minimum-scale=' + scale + ', user-scalable=no');
        document.documentElement.style.fontSize = document.documentElement.clientWidth / 10 + 'px';

        document.getElementById("content").setAttribute("style", 'height:' + document.documentElement.clientHeight + 'px');

        inputs.addEventListener("blur", function (event) {

            if (this.value.length === 6) {
                var data = {
                    'code': this.value,
                    'userid': '{{$data['userId']}}'
                };
                $.ajax({
                    type: 'POST',
                    url: '/api/invote/query',
                    data: data,
                    success: function (response) {
                        if (response.data.result === true) {
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
        })
    }
</script>
</html>

<script>
    new Vue({
        el: '#content',
        data: {},
        methods: {
            redirectTo: function () {
                wx.miniProgram.navigateTo({url: "/subPage/crouwdUp/crouwdUp"})
            }
        }
    })


</script>
