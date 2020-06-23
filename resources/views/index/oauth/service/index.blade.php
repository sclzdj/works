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
        	text-align: center;
        }

        .imgView {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top:1.5rem;
            margin-bottom: 50px;
}

        .imgView img {
            width: 240px;
            height: 240px;
            border-radius: 50%;
        }

        
.titleOne {
  text-align: center;
  font-size: 22px;
  font-family: PingFang SC;
  font-weight: bold;
  color: rgba(50, 50, 50, 1);
}

.contant {
  text-align: center;
  font-size: 15px;
  font-family: PingFang SC;
  font-weight: 500;
  color: rgba(150, 150, 150, 1);
  margin-top: 26px;
}

.guid-button {
  width: 300px;
  height: 44px;
  background: rgba(62, 205, 246, 1);
  border-radius: 5px;
  color: #fff;
  font-size: 18px;
  line-height:44px;
  border-radius: 44px;
  position: fixed;
  bottom: 1.5rem;
  bottom: calc(1.5rem + constant(safe-area-inset-bottom));
  bottom: calc(1.5rem + env(safe-area-inset-bottom));
  left: 50%;
  margin-left: -150px;
  text-align: center;
}
         


    </style>

    <script type="text/javascript" charset="utf-8">

        wx.config(<?php echo $app->jssdk->buildConfig(array('checkJSApi'), false) ?>);

    </script>



</head>

<body>

<div class='imgView'>
    <img src="{{asset('images/imgTitle.png')}}" alt="">
</div>

<div class='titleOne'>内测申请成功！</div>

<div class="contant">
   <div>审核结果将于10天内通过云作品微信</div>
   <div>公众号推送，请及时关注。</div>
</div>

<div class='guid-button'>
    <a href="http://mp.weixin.qq.com/mp/getmasssendmsg?__biz=MjM5MzkwNTMxNQ==#wechat_redirect"">
	   <div class='guid-button' hover-class="hover" bindtap="goAdd" style="background:linear-gradient(-28deg,rgba(3,0,0,0.15),rgba(255,255,255,0.15));">前往关注公众号</div>
    </a>
	</div>




<script src="{{asset('/static/admin/js/core/jquery.min.js')}}"></script>



<script>

    new Vue({

        el: '#content',

        data: {

            inputData:"",
			showTest:false,
			content:'',
			inputPlace:'请输入创建码'
        },

        methods: {


        }

    })



</script>

</html>

