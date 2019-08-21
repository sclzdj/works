$(function () {
    $('.tags-input').tagsInput({
        'height': 'auto',
        'width': '100%',
        'defaultText': '输入标签后敲回车即可添加',
        'minChars' : 0,
        'maxChars' : 100000, // if not provided there is no limit
        'placeholderColor' : '#aaaaaa'
    });
});
