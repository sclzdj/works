$(function () {
    //单点提交函数
    function idSubmit(fn) {
        var url = $(fn).attr('href');
        var type = $(fn).attr('submit-type');
        var status = $(fn).attr('submit-status');//操作状态：以后可能用到
        Dolphin.loading();
        $.ajax({
            type: type,
            url: url,
            dataType: 'JSON',
            success: function (response) {
                if (response.status_code >= 200 && response.status_code < 300) {
                    if (response.data.url !== undefined) {
                        Dolphin.jNotify(response.message, 'success', response.data.url, 1000);
                    } else {
                        Dolphin.rNotify(response.message, 'success', 1000);
                    }
                } else {
                    Dolphin.loading('hide');
                    Dolphin.notify(response.message, 'danger');
                }
            },
            error: function (xhr, status, error) {
                var response = JSON.parse(xhr.responseText);
                Dolphin.loading('hide');
                if (xhr.status == 419) { // csrf错误，错误码固定为419
                    Dolphin.notify('请勿重复请求~', 'danger');
                } else {
                    if (response.message) {
                        Dolphin.notify(response.message, 'danger');
                    } else {
                        Dolphin.notify('服务器错误~', 'danger');
                    }
                }
            }
        });
    }

    //单点提交执行
    $(document).delegate('.id-submit', 'click', function () {
        var fn = this;
        var confirm = $(this).attr('confirm');
        if (confirm !== undefined) {
            //询问框
            layer.confirm(confirm, {
                title: '警告',
                btn: ['确定', '取消'] //按钮
            }, function (index) {
                layer.close(index);
                idSubmit(fn);
            });
            return false;
        } else {
            idSubmit(fn);
            return false;
        }
    });

    // 移动地区保存排序
    $(document).delegate('#save', 'click', function () {
        var url = $(this).attr('href');
        var type = $(this).attr('submit-type');
        var sort_list = $('#area_list').nestable('serialize');
        var pid = $('#area_list').attr('pid');
        Dolphin.loading();
        $.ajax({
            type: type,
            url: url,
            dataType: 'JSON',
            data: {sort_list: sort_list,pid:pid},
            success: function (response) {
                if (response.status_code >= 200 && response.status_code < 300) {
                    if (response.data.url !== undefined) {
                        Dolphin.jNotify(response.message, 'success', response.data.url, 1000);
                    } else {
                        Dolphin.rNotify(response.message, 'success', 1000);
                    }
                } else {
                    Dolphin.loading('hide');
                    Dolphin.notify(response.message, 'danger');
                }
            },
            error: function (xhr, status, error) {
                var response = JSON.parse(xhr.responseText);
                Dolphin.loading('hide');
                if (xhr.status == 419) { // csrf错误，错误码固定为419
                    Dolphin.notify('请勿重复请求~', 'danger');
                } else {
                    if (response.message) {
                        Dolphin.notify(response.message, 'danger');
                    } else {
                        Dolphin.notify('服务器错误~', 'danger');
                    }
                }
            }
        });
        return false;
    });

    // 初始化地区拖拽
    $('#area_list').nestable({maxDepth: 1,group:1}).on('change', function () {
        $('#save').removeAttr("disabled").removeClass('btn-default disabled').addClass('btn-success');
    });

    // 隐藏禁用地区
    $('#hide_disable').click(function () {
        $('.dd-disable').hide();
    });

    // 显示禁用地区
    $('#show_disable').click(function () {
        $('.dd-disable').show();
    });

    // 展开所有地区
    $('#expand-all').click(function () {
        $('#area_list').nestable('expandAll');
    });

    // 收起所有地区
    $('#collapse-all').click(function () {
        $('#area_list').nestable('collapseAll');
    });
});
