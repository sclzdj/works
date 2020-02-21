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
        img.baidu-cloud {
            margin-top: 140px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 10px;
            display: block;
            width: 130px;
            height: 100px;
        }

        .bind-success-text {
            font-size: 16px;
            text-align: center;
            line-height: 20px;
            color: #969696;
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
    <script>
        document.body.addEventListener('touchmove', function (e) {
            e.preventDefault();
        }, {passive: false});
    </script>
</head>
<body style="height: 100%;background: #fff;">
<img src="{{asset('/images/baidu-cloud.png').'?'.$SFV}}" alt="百度云" class="baidu-cloud">
<div class="bind-success-text">百度网盘绑定成功</div>
<div class="bind-success-box">右滑两次进入网盘</div>
</body>
</html>
