$(function () {
    //批量提交函数
    function idsSubmit(fn, ids) {
        var url = $(fn).attr('href');
        var type = $(fn).attr('submit-type');
        Dolphin.loading();
        $.ajax({
            type: type,
            url: url,
            dataType: 'JSON',
            data: {ids: ids},
            success: function (response) {
                if (response.status_code >= 200 && response.status_code < 300) {
                    if (response.data.url !== undefined) {
                        Dolphin.jNotify(response.message, 'success', response.data.url);
                    } else {
                        Dolphin.rNotify(response.message, 'success');
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

    //批量提交执行
    $(document).on('click', '.ids-submit', function () {
        var ids = [];
        var fn = this;
        $('input[name="ids[]"]:checked').each(function () {
            ids.push($(this).val());
        });
        if (ids.length == 0) {
            Dolphin.notify('请先选择数据', 'warning');
            return false;
        }
        var confirm = $(this).attr('confirm');
        if (confirm !== undefined) {
            //询问框
            layer.confirm(confirm, {
                title: '警告',
                btn: ['确定', '取消'] //按钮
            }, function (index) {
                layer.close(index);
                idsSubmit(fn, ids);
            });
            return false;
        } else {
            idsSubmit(fn, ids);
            return false;
        }
    });

    //单点提交函数
    function idSubmit(fn) {
        var url = $(fn).attr('href');
        var type = $(fn).attr('submit-type');
        Dolphin.loading();
        $.ajax({
            type: type,
            url: url,
            dataType: 'JSON',
            success: function (response) {
                if (response.status_code >= 200 && response.status_code < 300) {
                    if (response.data.url !== undefined) {
                        Dolphin.jNotify(response.message, 'success', response.data.url);
                    } else {
                        Dolphin.rNotify(response.message, 'success');
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
    $(document).on('click', '.id-submit', function () {
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

    //开关提交
    $(document).on('change', '.switch-submit', function () {
        var url = '';
        var type = $(this).attr('submit-type');
        var checked = $(this).is(':checked');
        if (checked) {
            url = $(this).attr('href-on');
        } else {
            url = $(this).attr('href-off');
        }
        var _this = $(this);
        Dolphin.loading();
        $.ajax({
            type: type,
            url: url,
            dataType: 'JSON',
            success: function (response) {
                Dolphin.loading('hide');
                if (response.status_code >= 200 && response.status_code < 300) {
                    Dolphin.notify(response.message, 'success');
                } else {
                    _this.prop('checked', !checked);
                    Dolphin.notify(response.message, 'danger');
                }
            },
            error: function (xhr, status, error) {
                _this.prop('checked', !checked);
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
});
