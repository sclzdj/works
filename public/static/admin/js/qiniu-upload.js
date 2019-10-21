$(function () {
    $(document).on('click', 'input.qiniu-file-upload-trigger', function () {
        $(this).next('input.qiniu-file-upload').trigger('click');
    });
    $(document).on('click','button.qiniu-file-upload-clear',function(){
        $(this).parent().parent().find('input[readonly]').val('');
    });
    $(document).on('change', 'input.qiniu-file-upload', function (e) {
        var that = $(this);
        that.parent().parent().find('.form-validate-msg').remove();
        var files = e.target.files;
        var upload_max_size = that.attr('upload-max-size');
        if (upload_max_size === undefined) {
            upload_max_size = 0;
        } else {
            upload_max_size = parseInt(upload_max_size);
        }
        var mimeType = that.attr('mime-type');
        if (mimeType === undefined) {
            mimeType = null;
        }
        var valueType = that.attr('value-type');
        if (valueType === undefined) {
            valueType = 'url';
        }
        if (files !== undefined && files[0] !== undefined) {
            if (upload_max_size>0 && upload_max_size < files[0].size) {
                that.parent().parent().append('<div class="col-md-11 col-md-offset-1 form-validate-msg form-option-line"><span class="text-warning">上传文件大小超过限制</span></div>');
                return false;
            }
            var observable = qiniu.upload(files[0], null, qiniu_config.upToken, {
                fname: "",
                params: {},
                mimeType: mimeType
            }, config = {
                useCdnDomain: true,
                region: null
            });
            var subscription = observable.subscribe({
                next(res) {
                    var className = 'text-primary';
                    if (res.total.percent >= 100) {
                        className = 'text-success';
                    }
                    that.parent().parent().find('.qiniu-percent').remove();
                    that.parent().parent().append('<div class="col-md-11 col-md-offset-1 form-validate-msg form-option-line qiniu-percent"><span class="' + className + '">上传进度：' + Math.floor(res.total.percent) + '%</span></div>');
                },
                error(err) {
                    that.parent().parent().append('<div class="col-md-11 col-md-offset-1 form-validate-msg form-option-line qiniu-percent"><span class="text-warning">上传失败：' + err.message + '</span></div>');
                },
                complete(res) {
                    that.prev('input').val(qiniu_config.domain + '/' + res.key);
                    if(valueType=='key'){
                        that.prev('input').val(res.key);
                    }else{
                        that.prev('input').val(qiniu_config.domain + '/' + res.key);
                    }
                }
            });
        }
    });
});
