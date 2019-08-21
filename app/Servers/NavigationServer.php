<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/16
 * Time: 16:57
 */

namespace App\Servers;


use App\Model\Admin\SystemNode;

class NavigationServer
{
    /**
     * 首页链接
     *
     * @return string
     */
    public static function homeUrl()
    {
        if (isset($GLOBALS['navigation']['homeUrl'])) {
            return $GLOBALS['navigation']['homeUrl'];
        }
        $modules = self::modules();
        foreach ($modules as $module) {
            if ($module['url'] !== '') {
                return $module['url'];
            }
        }

        return $GLOBALS['navigation']['homeUrl'] = '';
    }

    public static function moduleUrl($id)
    {
        $modules = self::modules();
        foreach ($modules as $module) {
            if ($module['id'] == $id) {
                return $module['url'];
            }
        }

        return '';
    }

    /**
     * 导航栏模块
     *
     * @return mixed
     */
    public static function modules()
    {
        if (isset($GLOBALS['navigation']['modules'])) {
            return $GLOBALS['navigation']['modules'];
        }
        $modules = self::alls(0, 3);
        foreach ($modules as $key => $module) {
            $modules[$key]['url'] = '';
            if ($module['action'] !== '') {
                $modules[$key]['url'] = action($module['action']);
                continue;
            } else {
                if (isset($module['_data']) && count($module['_data']) > 0) {
                    foreach ($module['_data'] as $k2 => $v2) {
                        if ($v2['action'] !== '') {
                            $modules[$key]['url'] = action($v2['action']);
                            continue 2;
                        } else {
                            if (isset($v2['_data']) &&
                                count($v2['_data']) > 0
                            ) {
                                foreach ($v2['_data'] as $k3 => $v3) {
                                    if ($v3['action'] !== '') {
                                        $modules[$key]['url'] =
                                            action($v3['action']);
                                        continue 3;
                                    } else {
                                        if (isset($v3['_data']) &&
                                            count($v3['_data']) > 0
                                        ) {
                                            foreach ($v3['_data'] as $k4 => $v4)
                                            {
                                                if ($v4['action'] !== '') {
                                                    $modules[$key]['url'] =
                                                        action($v4['action']);
                                                    continue 4;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($modules[$key]);
        }

        return $GLOBALS['navigation']['modules'] = $modules;
    }

    /**
     * 当前模块id
     *
     * @return int|mixed
     */
    public static function currentModuleId()
    {
        if (isset($GLOBALS['navigation']['currentModuleId'])) {
            return $GLOBALS['navigation']['currentModuleId'];
        }
        $actionName = request()->route()->getActionName();
        $systemNode = SystemNode::where('action',
                                        str_replace('App\Http\Controllers\\',
                                                    '', $actionName))->first();

        $currentModuleId = 0;
        if ($systemNode) {
            $currentModuleId = SystemNode::rootNode($systemNode->id, 1);
        }

        return $GLOBALS['navigation']['currentModuleId'] = $currentModuleId;
    }

    /**
     * 左侧菜单栏
     *
     * @return mixed
     */
    public static function menus()
    {
        $currentModuleId = self::currentModuleId();
        $pid = $currentModuleId == 0 ?
            1 :
            $currentModuleId;

        if (isset($GLOBALS['navigation']['menus'][$pid])) {
            return $GLOBALS['navigation']['menus'][$pid];
        }

        return $GLOBALS['navigation']['menus'][$pid] = self::alls($pid, 2);
    }

    /**
     *
     *左侧菜单高亮显示
     *
     * @param $action
     *
     * @return bool
     */
    public static function activeMenu($action)
    {
        $actionName = request()->route()->getActionName();
        $actionName = str_replace('App\Http\Controllers\\', '', $actionName);
        $systemNode = SystemNode::where('action', $actionName)->first();
        if (!$systemNode) {
            return false;
        }
        if ($systemNode->level > 3) {
            $roots = SystemNode::elderNodes($systemNode->id);
        }
        if (isset($roots[2])) {
            $actionName = $roots[2]['action'];
        }
        if ($action == $actionName) {
            return true;
        } else {
            return false;
        }
    }

    public static function location()
    {
        $actionName = request()->route()->getActionName();
        $systemNode = SystemNode::where('action',
                                        str_replace('App\Http\Controllers\\',
                                                    '', $actionName))->first();
        if ($systemNode) {
            $elderNodes = SystemNode::elderNodes($systemNode['id']);
            $elderNodes[] = $systemNode;
        }

        return $elderNodes;
    }

    /**
     * 所有有效导航节点
     *
     * @return mixed
     */
    public static function alls($pid = 0, $max_level = 4)
    {
        if (isset($GLOBALS['navigation']['alls'][$pid][$max_level])) {
            return $GLOBALS['navigation']['alls'][$pid][$max_level];
        }

        return $GLOBALS['navigation']['alls'][$pid][$max_level] =
            self::_all($pid, $max_level);
    }

    protected static function _all($pid = 0, $max_level = 4)
    {
        $allowNodes = PermissionServer::allowNodesOne();

        if ($allowNodes !== true) {
            $alls =
                SystemNode::grMaxNodes($pid, 1, '', $max_level, 1, $allowNodes);

        } else {
            $alls = SystemNode::grMaxNodes($pid, 1, '', $max_level);
        }


        return $alls;
    }

}
