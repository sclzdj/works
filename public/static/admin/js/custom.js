/*!
* 后台自定义js
*/


$(function () {
    $('li.du-menu-status').each(function(){
        if($(this).find('ul li a.active').length>0){
            $(this).addClass('open');
            return false;
        }
    });
});
