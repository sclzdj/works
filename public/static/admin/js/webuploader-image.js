$(function () {
    //先预设一个文件名，然后每个上传器的文件名为filename_uploader_image+index
    var now_uploader_image = new Date();
    var moth_uploader_image = String(now_uploader_image.getMonth() + 1);
    for (var len = moth_uploader_image.length; len < 2; len = moth_uploader_image.length) {
        moth_uploader_image = "0" + moth_uploader_image;
    }
    var day_uploader_image = String(now_uploader_image.getDate());
    for (var len = day_uploader_image.length; len < 2; len = day_uploader_image.length) {
        day_uploader_image = "0" + day_uploader_image;
    }
    var date_uploader_image = String(now_uploader_image.getFullYear()) + moth_uploader_image + day_uploader_image;
    var time_uploader_image = String(now_uploader_image.getTime());
    var filename_uploader_image = date_uploader_image + '/' + time_uploader_image + String(Math.floor(Math.random() * 10000));
    //先预设一个场景数组
    var scene_uploader_image = set_scene_uploader_image === undefined ? [] : set_scene_uploader_image;
    // 图片上传初始化Web Uploader
    var uploader_image = [];
    for (var index = 0; index < $('.js-upload-image').length; index++) {
        scene_uploader_image[index] = scene_uploader_image[index] === undefined ? '' : scene_uploader_image[index];
        var upload_type = $('.js-upload-image:eq(' + index + ')').attr('upload-type');
        uploader_image[index] = WebUploader.create({
            swf: './static/libs/webuploader/Uploader.swf',// swf文件路径
            server: server_upload_image_url,// 文件接收服务端
            // 内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: {
                id: '.js-upload-image:eq(' + index + ') .filePicker', // 选择文件的按钮。可选。
                multiple: upload_type == 'images' ? true : false // 是否多选
            },
            // 只允许选择图片文件。
            accept: {
                title: upload_type, // 文字描述
                extensions: server_upload_image_allow_extension, //允许的文件后缀，不带点，多个用逗号分割
                mimeTypes: 'image/*' //文件mime类型
            },
            //附带参数
            formData: {
                'upload_type': upload_type,
                'filename': filename_uploader_image + index,
                'scene': scene_uploader_image[index]
            },
            auto: true, // 选完文件后，是否自动上传
            fileVal: 'file', //设置文件上传域的name
            method: 'POST', //文件上传方式
            fileNumLimit: undefined, //验证文件总数量, 超出则不允许加入队列，默认undefined
            fileSizeLimit: undefined, //验证文件总大小是否超出限制, 超出则不允许加入队列，默认undefined
            fileSingleSizeLimit: server_upload_image_limit_size > 0 ? server_upload_image_limit_size : undefined, //验证单个文件大小是否超出限制, 超出则不允许加入队列，默认undefined
            duplicate: true //为true允许重复上传同张图片
        });
        //标记这是第几个图片上传
        uploader_image[index].index = index;
        //记录上传文件
        uploader_image[index].files = [];
        if (upload_type == 'images') {
            //标记上传表单名称
            uploader_image[index].inputName = $('.js-upload-image:eq(' + index + ')').attr('input-name');
        }
        //标记上传类型
        uploader_image[index].upload_type = upload_type;
        // 当开始上传流程时触发
        uploader_image[index].on('startUpload', function () {
            Dolphin.loading();
        });
        // 当有文件添加进来的时候
        uploader_image[index].on('fileQueued', function (file) {
            var $li = $(
                '<div id="' + file.id + '" class="file-item js-gallery thumbnail">' +
                '<img class="uploader-img">' +
                '<div class="info">' + file.name + '</div>' +
                '</div>'
                ),
                $img = $li.find('img');
            // $list为容器jQuery实例
            if (this.upload_type == 'images') {
                $('.js-upload-image:eq(' + this.index + ') .uploader-list').append($li);
            } else {
                $('.js-upload-image:eq(' + this.index + ') .uploader-list').empty();
                $('.js-upload-image:eq(' + this.index + ') .uploader-list').html($li);
            }
            //记录上传文件
            uploader_image[this.index].files[file.id] = file;
            // 创建缩略图
            // 如果为非图片文件，可以不用调用此方法。
            // thumbnailWidth x thumbnailHeight 为 100 x 100
            var thumbnailWidth = 100,
                thumbnailHeight = 100;
            uploader_image[this.index].makeThumb(file, function (error, src) {
                if (error) {
                    $img.replaceWith('<span class="none-view">不能预览</span>');
                    return;
                }
                $img.prop('src', src);
            }, thumbnailWidth, thumbnailHeight);
        });
        // 文件上传过程中创建进度条实时显示。
        uploader_image[index].on('uploadProgress', function (file, percentage) {
            var $li = $('#' + file.id),
                $percent = $li.find('.progress');
            $li.find('div.error,div.retry').remove();
            // 避免重复创建
            if (!$percent.length) {
                $percent = $('<div class="progress"><div class="progress-run"></div><div class="progress-percent"></div></div>')
                    .appendTo($li);
            }
            $percent.find('.progress-run').css('width', percentage * 100 + '%');
            percentageRate = percentage * 100;
            $percent.find('.progress-percent').text(percentageRate.toFixed(2) + '%');
        });
        // 文件上传成功，给item添加成功class, 用样式标记上传成功。
        uploader_image[index].on('uploadSuccess', function (file, response) {
            if (response.status_code < 200 || response.status_code >= 300) {
                var $li = $('#' + file.id),
                    $error = $li.find('div.error'),
                    $retry = $li.find('div.retry');
                // 避免重复创建
                if (!$error.length) {
                    $error = $('<div class="error"></div>').appendTo($li);
                }
                if (!$retry.length) {
                    $retry = $('<div class="retry"></div>').appendTo($li);
                }
                $error.text(response.message);
                $retry.html('<a href="javascript:void(0);" uploader-index="' + this.index + '" file-id="' + file.id + '" class="uploader-retry text-primary">重试上传</a>');
            } else {
                $('#' + file.id).addClass('upload-state-done');
                //图片查看器赋值
                if ($('#' + file.id).find('img').length == 0) {
                    $('#' + file.id).find('span.none-view').remove();
                    $('#' + file.id).prepend('<img class="uploader-img">');
                }
                if (server_upload_default_filesystems == 'local' && !inArray(scene_uploader_image[this.index], server_upload_image_special_scenes)) {
                    $('#' + file.id).find('img').prop('src', server_image_host + response.data.url + '&type=2&' + Math.random());
                    $('#' + file.id).find('img').attr('data-original', server_image_host + response.data.url + '&type=1&' + Math.random());
                } else {
                    $('#' + file.id).find('img').prop('src', server_image_host + response.data.url + '?' + Math.random());
                    $('#' + file.id).find('img').attr('data-original', server_image_host + response.data.url + '?' + Math.random());
                }
                //viewer更新加载
                $('.gallery-list,.uploader-list').each(function () {
                    $(this).viewer('update');
                    $(this).viewer('destroy');
                    $(this).viewer({url: 'data-original'});
                });
                if (this.upload_type == 'images') {
                    //将上传的文件地址赋值给隐藏输入框，并添加元素
                    $('#' + file.id).append('<input type="hidden" name="' + this.inputName + '[]" value="' + server_image_host + response.data.url + '">');
                } else {
                    //将上传的文件地址赋值给隐藏输入框
                    $('#' + file.id).parent().parent().find('input[type="hidden"]').val(server_image_host + response.data.url);
                }
                //成功提示
                var $li = $('#' + file.id),
                    $success = $li.find('div.success');
                // 避免重复创建
                if (!$success.length) {
                    $success = $('<div class="success"></div>').appendTo($li);
                }
                $success.text('上传成功');
                //删除原有提示
                $li.find('div.error,div.retry').remove();
            }
        });
        // 文件上传失败，显示上传出错
        uploader_image[index].on('uploadError', function (file, reason) {
            var $li = $('#' + file.id),
                $error = $li.find('div.error'),
                $retry = $li.find('div.retry');
            // 避免重复创建
            if (!$error.length) {
                $error = $('<div class="error"></div>').appendTo($li);
            }
            if (!$retry.length) {
                $retry = $('<div class="retry"></div>').appendTo($li);
            }
            $error.text('上传失败');
            $retry.html('<a href="javascript:void(0);"  uploader-index="' + this.index + '" file-id="' + file.id + '" class="uploader-retry text-primary">重试上传</a>');
        });
        // 完成上传完了，成功或者失败，先删除进度条
        uploader_image[index].on('uploadComplete', function (file) {
            $('#' + file.id).find('.progress').remove();
            //添加删除按钮
            $('#' + file.id).append('<i class="fa fa-times-circle remove-picture" uploader-index="' + this.index + '" file-id="' + file.id + '"></i>');
            if (this.upload_type == 'images') {
                //添加拖拽按钮
                $('#' + file.id).append('<i class="fa fa-fw fa-arrows move-picture"></i>');
            }
        });
        // 当所有文件上传结束时触发
        uploader_image[index].on('uploadFinished', function () {
            Dolphin.loading('hide')
        });
    }


    //移除图片
    $(document).on('click', '.remove-picture', function () {
        //单图上传时需重置表单值
        $(this).parent().parent().parent().find('input[type="hidden"]').val('');
        //移除元素
        $(this).parent().remove();
        //移除队列中的对应图片
        var index = $(this).attr('uploader-index');
        var fileId = $(this).attr('file-id');
        if (fileId !== undefined) {
            uploader_image[index].removeFile(uploader_image[index].files[fileId], true);
        }
        //viewer更新加载
        $('.gallery-list,.uploader-list').each(function () {
            $(this).viewer('update');
            $(this).viewer('destroy');
            $(this).viewer({url: 'data-original'});
        });
    });

    //重试上传
    $(document).on('click', '.uploader-retry', function () {
        var index = $(this).attr('uploader-index');
        var fileId = $(this).attr('file-id');
        uploader_image[index].retry(uploader_image[index].files[fileId]);
    });

});
