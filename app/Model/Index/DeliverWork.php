<?php
namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 交付助手作品模型
 * @package App\Model\Index
 * @author jsyzchenchen@gmail.com
 * @date 2020/07/18
 */
class DeliverWork extends Model
{
    use SoftDeletes;

    /**
     * 需要转换成日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 获取作品下的文件列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/25
     */
    public function files()
    {
        return $this->hasMany('App\Model\Index\DeliverWorkFile', 'work_id', 'id');
    }
}
