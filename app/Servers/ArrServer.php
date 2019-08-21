<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/15
 * Time: 22:06
 */

namespace App\Servers;

class ArrServer
{
    /**
     * 获取只有id的数据
     *
     * @param array  $data
     * @param string $field
     * @param int    $type 0返回数组  1返回字符串
     *
     * @return array|string
     */
    public static function ids($data = [], $field = 'id', $type = 0)
    {
        $ids = [];
        foreach ($data as $d) {
            $ids[] = $d[$field];
        }
        if ($type != 1) {
            return $ids;
        } else {
            return implode(',', $ids);
        }
    }

    /**
     * @param array  $data
     * @param string $key_field
     * @param string $value_field
     *
     * @return array
     */
    public static function options($data = [], $key_field = 'id',
        $value_field = 'name'
    ) {
        $options = [];
        foreach ($data as $d) {
            $options[$d[$key_field]] = $d[$value_field];
        }

        return $options;
    }

    /**
     * 保留数组有效部分并排除数组无效部分，方便入库
     *
     * @param       $data
     * @param array $retain
     * @param array $except
     *
     * @return mixed
     */
    public static function inData($data, $retain = [], $except = [])
    {
        if ($retain || $except) {
            foreach ($data as $k => $v) {
                if ($retain && !in_array($k, $retain)) {
                    unset($data[$k]);
                }
                if ($except && in_array($k, $except)) {
                    unset($data[$k]);
                }
            }
        }

        return $data;
    }

    public static function null2strData($data)
    {
        $data = array_map(function ($value) {
            if ($value === null) {
                return '';
            } else {
                return $value;
            }
        }, $data);

        return $data;
    }

    /**
     * 递归解析数组
     *
     * @param array $data  数据
     * @param int   $pid   上级节点id
     * @param model $model 模型名称
     *
     * @return array 返回入库后的所有对象合成一个数组
     */
    public static function parseData($data = [],
        $model = 'App\Model\Admin\SystemNode', $pid = 0, $level = 1
    ) {
        $sort = 1;
        $result = [];

        foreach ($data as $d) {
            $pix = [
                'pid' => (int)$pid,
                'sort' => $sort,
                'level' => $level,
            ];
            $tmp = $d;
            if (isset($tmp['children'])) {
                unset($tmp['children']);
            }
            $new = $model::create(array_merge($tmp, $pix));
            $result[] = $new;
            if (isset($d['children'])) {
                $result = array_merge($result,
                                      self::parseData($d['children'], $model,
                                                      $new->id, $level + 1));
            }
            $sort++;
        }

        return $result;
    }
}
