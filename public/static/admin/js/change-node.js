$(function () {
    $(document).delegate('input[name="system_node_ids[]"]', 'change', function () {
        var level = $(this).attr('level');
        if ($(this).prop('checked')) {
            if (level < 4) {
                var inputs = $(this).parent().parent().nextAll('.small-input-checkbox').find('input[name="system_node_ids[]"]');
                inputs.each(function () {
                    if ($(this).attr('level') > level) {
                        $(this).prop('checked', true);
                    } else {
                        return false;
                    }
                });
            }
            var inputs = $(this).parent().parent().prevAll('.small-input-checkbox').find('input[name="system_node_ids[]"]');
            if (level > 1) {
                var level_bj = level - 1;
                for (var i = inputs.length-1; i >= 0; i--) {
                    if (level_bj >= 1) {
                        if ($(inputs[i]).attr('level') == level_bj) {
                            $(inputs[i]).prop('checked', true);
                            level_bj--;
                        }
                    } else {
                        break;
                    }
                }
            }
        } else {
            if (level < 4) {
                var inputs = $(this).parent().parent().nextAll('.small-input-checkbox').find('input[name="system_node_ids[]"]');
                inputs.each(function () {
                    if ($(this).attr('level') > level) {
                        $(this).prop('checked', false);
                    } else {
                        return false;
                    }
                });
            }
        }
    });
});
