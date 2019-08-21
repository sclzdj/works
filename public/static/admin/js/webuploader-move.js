$(function () {
    //多图上传拖拽排序
    $('.ui-images-sortable').sortable({
        placeholder: "ui-sortable-images-state-highlight",
        handle: ".move-picture"
    });
    $(".ui-images-sortable").disableSelection();

    //多文件上传拖拽排序
    $('.ui-files-sortable').sortable({
        handle: ".move-file"
    });
    $(".ui-files-sortable").disableSelection();
});
