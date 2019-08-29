<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemArea extends Model
{
    public $timestamps = false;//关闭时间维护
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'pid',
        'short_name',
        'level',
        'sort',
        'position',
        'lng',
        'lat',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'pid',
            'name',
            'short_name',
        ];
    }

    /**
     * 完全填充数据
     */
    public static function fillAll()
    {
        self::_fillAll();
    }

    public static function _fillAll()
    {
        $file_name = asset('/data/sql/system_areas.sql');
        $DB_HOST = getenv('DB_HOST');
        $DB_DATABASE = getenv('DB_DATABASE'); //从配置文件中获取数据库信息
        $DB_USERNAME = getenv('DB_USERNAME');
        $DB_PASSWORD = getenv('DB_PASSWORD');
        set_time_limit(0); //设置超时时间为0，表示一直执行。当php在safe mode模式下无效，此时可能会导致导入超时，此时需要分段导入
        $fp = @fopen($file_name, "r") or die("不能打开SQL文件 $file_name");//打开文件
        @$conf = mysqli_connect(
            $DB_HOST,
            $DB_USERNAME,
            $DB_PASSWORD,
            $DB_DATABASE
        ) or die("不能连接数据库 $DB_HOST");//连接数据库
        mysqli_query($conf, "SHOW tables");
        // 导入数据库的MySQL命令
        $_sql = file_get_contents($file_name);
        $_arr = explode(';', $_sql);
        foreach ($_arr as $_value) {
            mysqli_query($conf, "SET NAMES 'utf8'");
            mysqli_query($conf, $_value.';');
        }
    }


    /**
     * 查找某地区的所有后代地区
     *
     * @param int $pid 地区id
     * @param string $status 条件
     * @param int $type 返回类型 0=>所有信息的二维数组，1=>只有id的一维数组
     * @param int $level 这个参数不传递
     *
     * @return array|string
     */
    public static function progenyAreas(
        $id,
        $status = '',
        $type = 0,
        $level = 1
    ) {
        static $data = [];
        $where = ['pid' => $id];
        //        if ($status !== '') {
        //            $where['status'] = $status;
        //        }
        $systemAreas =
            self::where($where)->orderBy('sort', 'asc')->get()->toArray();
        foreach ($systemAreas as $key => $systemArea) {
            $systemArea['_level'] = $level;
            if ($type == 1) {
                $data[] = $systemArea['id'];
            } else {
                $data[] = $systemArea;
            }
            $data = self::progenyAreas(
                $systemArea['id'],
                $status,
                $type,
                $level + 1
            );
        }

        return $data;
    }

    /**
     * 查找某地区的所有直属长辈地区
     *
     * @param int $pid 地区id
     * @param int $type 返回类型 0=>所有信息的二维数组，1=>只有id的一维数组
     *
     * @return array|string
     */
    public static function elderAreas($id, $type = 0)
    {
        $elderAreas = self::_elderAreas($id);
        $elderAreas = array_reverse($elderAreas);
        if ($type == 0) {
            $data = [];
            foreach ($elderAreas as $v) {
                $data[] = self::find($v);
            }

            return $data;
        } else {
            return $elderAreas;
        }
    }

    /*
     * 查出所有长辈地区，顺序从父级到根级
     * 切记此处返回数据千万不要用静态变量，会出现问题，具体不清楚
     */
    protected static function _elderAreas($id, $data = [])
    {
        $systemArea = self::find($id);
        if ($systemArea && $systemArea->pid > 0 &&
            $pSystemArea = self::find($systemArea->pid)
        ) {
            $data[] = $pSystemArea->id;

            return self::_elderAreas($pSystemArea->id, $data);
        } else {
            return $data;
        }


    }

    /**
     * 根地区
     *
     * @param $id
     */
    public static function rootArea($id, $type = 0)
    {
        $roots = self::elderAreas($id, 1);

        $root_id = count($roots) > 0 ?
            $roots[0] :
            $id;
        if ($type == 0) {
            return self::find($root_id);
        } else {
            return $root_id;
        }
    }

    /**
     * 衍生无限级分类
     *
     * @param int $pid 父级地区开始查，传0查全部
     * @param string $status 查询条件
     * @param string $html 级别文本
     * @param integer $max_level 查出层数
     * @param int $level 这个参数不传递
     *
     * @return mixed 多维数组
     */
    public static function grMaxAreas(
        $pid = 0,
        $status = '',
        $html = '&nbsp;│&nbsp;',
        $max_level = 0,
        $level = 1,
        $in = false
    ) {
        $where = ['pid' => $pid];
        //        if ($status !== '') {
        //            $where['status'] = $status;
        //        }
        if ($in !== false) {
            $systemAreas =
                self::where($where)->whereIn('id', $in)->orderBy('sort', 'asc')
                    ->get()->toArray();
        } else {
            $systemAreas =
                self::where($where)->orderBy('sort', 'asc')->get()->toArray();
        }
        foreach ($systemAreas as $key => $systemArea) {
            $systemAreas[$key]['_html'] = str_repeat($html, $level - 1);
            $systemAreas[$key]['_level'] = $level;
            if ($max_level == 0 || $level != $max_level) {
                $systemAreas[$key]['_data'] =
                    self::grMaxAreas(
                        $systemArea['id'],
                        $status,
                        $html,
                        $max_level,
                        $level + 1,
                        $in
                    );
            }
        }

        return $systemAreas;
    }

    /**
     * 树状无限级分类
     *
     * @param int $pid 父级地区开始查，传0查全部
     * @param string $status 查询条件
     * @param object $obj 修改页面的对象（主要用于selected和disabled）
     * @param string $html 级别文本
     * @param integer $max_level 查出层数
     * @param int $level 这个参数不传递
     *
     * @return mixed 一维数组
     */
    public static function treeAreas(
        $pid = 0,
        $status = '',
        $obj = '',
        $html = '&nbsp;│&nbsp;',
        $max_level = 0,
        $level = 1
    ) {
        static $data = [];
        static $disabledLevel = 0;
        static $disabled = false;
        $where = ['pid' => $pid];
        //        if ($status !== '') {
        //            $where['status'] = $status;
        //        }
        $systemAreas =
            self::where($where)->orderBy('sort', 'asc')->get()->toArray();
        foreach ($systemAreas as $key => $systemArea) {
            if ($obj && $disabledLevel < $level && $disabled) {
                $systemArea['_disabled'] = 'disabled';
            } else {
                $systemArea['_disabled'] = '';
            }
            if ($obj && $level <= $disabledLevel) {
                $disabled = false;
            }
            if ($obj && $systemArea['id'] == $obj['id']) {
                $systemArea['_disabled'] = 'disabled';
                $disabledLevel = $level;
                $disabled = true;
            }
            if ($obj && $systemArea['id'] == $obj['pid']) {
                $systemArea['_selected'] = 'selected';
            } else {
                $systemArea['_selected'] = '';
            }
            $systemArea['_html'] = str_repeat($html, $level - 1);
            $systemArea['_level'] = $level;
            $data[] = $systemArea;
            if ($max_level == 0 || $level != $max_level) {
                $data = self::treeAreas(
                    $systemArea['id'],
                    $status,
                    $obj,
                    $html,
                    $max_level,
                    $level + 1
                );
            }
        }

        return $data;
    }

    /**
     * 衍生无限级分类页面html结构 只用于地区管理页
     *
     * @param int $pid 父级地区开始查，传0查全部
     * @param string $status 查询条件
     * @param integer $max_level 显示层数
     *
     * @return mixed 多维数组
     */
    public static function grMaxHtml(
        $pid = 0,
        $status = '',
        $max_level = 0,
        $level = 1
    ) {
        $innerHtml = '';
        $where = ['pid' => $pid];
        //        if ($status !== '') {
        //            $where['status'] = $status;
        //        }
        $systemAreas =
            self::where($where)->orderBy('sort', 'asc')->get()->toArray();
        foreach ($systemAreas as $k => $v) {
            $disable = '';
            //            if ($v['status']) {
            //                $disable = '';
            //            } else {
            //                $disable = 'dd-disable';
            //            }
            $innerHtml .= '<li class="dd-item dd3-item '.$disable.
                '" data-id="'.$v['id'].
                '"><div class="dd-handle dd3-handle">拖拽</div>';
            $innerHtml .= '<div class="dd3-content"> <span class="dd3-level">'.
                $v['level'].'级地区</span>'.
                '<span data-toggle="tooltip" data-original-title="简称：'.
                $v['short_name'].'" style="cursor:default;">'.$v['name'].
                '</span>';
            $innerHtml .= '<div class="action">';
            if ($v['level'] < 4) {
                $innerHtml .= '<a href="'.
                    action(
                        'Admin\System\AreaController@index',
                        ['pid' => $v['id']]
                    ).
                    '" class="link-effect list-link">查看下级地区</a>';
            }
            $innerHtml .= '</div></div>';
            if ($max_level == 0 || $level != $max_level) {
                unset($systemAreas[$k]);
                $ii =
                    self::grMaxHtml($v['id'], $status, $max_level, $level + 1);
                if ($ii) {
                    $innerHtml .= '<ol class="dd-list">'.
                        self::grMaxHtml(
                            $v['id'],
                            $status,
                            $max_level,
                            $level + 1
                        ).'</ol>';
                }
            }
            $innerHtml .= '</li>';
        }

        return $innerHtml;
    }

    /**
     * 递归解析地区，主要用于排序
     *
     * @param array $menus 地区数据
     * @param int $pid 上级地区id
     *
     * @return array 解析成可以写入数据库的格式
     */
    public static function parseAreas(
        $data = [],
        $pid = 0,
        $level = 1,
        $status = 1,
        $status_level = 1
    ) {
        $sort = 1;
        $result = [];
        foreach ($data as $d) {
            $id = (int)$d['id'];
            if ($level <= $status_level) {
                $status = 1;
            }
            if ($status == 1) {
                $systemArea = self::find($id);
                if ($systemArea->status == 0) {
                    $status = 0;
                    $status_level = $level;
                }
            }
            $result[] = [
                'id' => $id,
                'pid' => (int)$pid,
                'sort' => $sort,
                'level' => $level,
                'status' => $status,
            ];
            if (isset($d['children'])) {
                $result = array_merge(
                    $result,
                    self::parseAreas(
                        $d['children'],
                        $id,
                        $level + 1,
                        $status,
                        $status_level
                    )
                );
            }
            $sort++;
        }

        return $result;
    }
}
