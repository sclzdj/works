<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>网盘授权</title>
</head>
<body>
<p>正在授权中</p>
<script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
<script src="{{asset('/static/admin/js/core/jquery.min.js')}}"></script>
<script>
    function par2Json(string, overwrite) {
        var obj = {}, pairs = string.split('&'), d = decodeURIComponent, name, value;
        $.each(pairs, function (i, pair) {
            pair = pair.split('=');
            name = d(pair[0]);
            value = d(pair[1]);
            obj[name] = value;
        });
        return obj;
    };
    $(function () {
        var data = par2Json(location.hash.slice(1));
        data.user_id = '{{$user_id}}';
        $.ajax({
            type: 'POST',
            url: '/api/baidu/oauth',
            dataType: 'JSON',
            data: data,
            success: function (response) {
                alert('授权成功');
                wx.miniProgram.navigateBack();
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
    });
</script>
</body>
</html>
