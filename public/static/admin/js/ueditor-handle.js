$(function () {
    // **百度编辑器单图上传不支持跨域，等待官方更新
    new UE.ui.Editor();
    for (var index = 0; index < $('.js-ueditor').length; index++) {
        $('.js-ueditor:eq(' + index + ')').prop('id', 'js-ueditor' + index);
        UE.getEditor('js-ueditor' + index, {
            initialFrameHeight: 800 //设置编辑器高度
        });
    }
    // 重写文件件上传方法
    UE.Editor.prototype._bkGetActionUrl = UE.Editor.prototype.getActionUrl;
    UE.Editor.prototype.getActionUrl = function (action) {
        switch (action) {
            case 'config':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/ueditorUploadConfig';
                break;
            case 'uploadimage':
            case 'uploadscrawl':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/upload?upload_type=image&scene=ueditor_upload'; //这就是自定义的上传地址
                break;
            case 'uploadvideo':
            case 'uploadfile':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/upload?upload_type=file&scene=ueditor_upload'; //这就是自定义的上传地址
                break;
            case 'listimage':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/ueditorList?type=image';
                break;
            case 'listfile':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/ueditorList?type=file';
                break;
            case 'catchimage':
                return document.location.protocol + '//' + window.location.host + '/api/admin/system/file/ueditorCatchImage?upload_type=image&scene=ueditor_catch_upload';
                break;
            default:
                return this._bkGetActionUrl.call(this, action);
        }
    }
});
