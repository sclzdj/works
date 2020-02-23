<!doctype html>
<html lang="en">
@php
    $SFV=\App\Model\Admin\SystemConfig::getVal('basic_static_file_version');
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <style>
        html,body{
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .big-box{
            position: relative;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }
        img.baidu-cloud {
            /*margin-top: 140px;*/
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 20px;
            display: block;
            width: 130px;
            height: 100px;
        }

        .bind-success-text {
            font-size: 12px;
            text-align: center;
            line-height: 20px;
            color: #969696;
            margin-bottom: 10px;
        }

        .bind-success-box {
            font-size: 16px;
            text-align: center;
            line-height: 44px;
            color: #ffffff;
            background: #3ECDF6;
            border-radius: 5px;
            width: 190px;
            margin: 25px auto;
        }
    </style>
</head>
<body>
<div class="big-box">
    <img src="{{asset('/images/baidu-cloud.png').'?'.$SFV}}" alt="百度云" class="baidu-cloud">
    <div class="bind-success-text">百度网盘绑定成功</div>
    <div class="bind-success-box">右滑两次进入网盘</div>
</div>
<div style="clear: both;"></div>
</body>
</html>
