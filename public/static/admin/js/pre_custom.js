/**
 * 判断是否在数组中
 * @param arr 数组
 * @param item 判断条目
 * @returns bool
 */
function inArray(item, arr) {
    var i = arr.length;
    while (i--) {
        if (arr[i] === item) {
            return true;
        }
    }
    return false;
}