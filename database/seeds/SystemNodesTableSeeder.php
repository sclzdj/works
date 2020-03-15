<?php

use Illuminate\Database\Seeder;

class SystemNodesTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //        $systemNodes = factory(\App\Model\Admin\SystemNode::class, 4)->create();
        //        foreach ($systemNodes as $systemNode) {
        //            $systemNode->pid = 0;
        //            $systemNode->action = '';
        //            $systemNode->level = 1;
        //            $systemNode->save();
        //        }
        //        $systemNode = $systemNodes[0];
        //        $systemNode->name = '系统';
        //        $systemNode->pid = 0;
        //        $systemNode->action = '';
        //        $systemNode->status = 1;
        //        $systemNode->sort = 0;
        //        $systemNode->save();
        //        $systemNode = $systemNodes[1];
        //        $systemNode->name = '系统主页';
        //        $systemNode->pid = 1;
        //        $systemNode->action = 'Admin\System\IndexController@index';
        //        $systemNode->status = 1;
        //        $systemNode->sort = 0;
        //        $systemNode->level = 2;
        //        $systemNode->save();
        //
        //        $systemNodes = factory(\App\Model\Admin\SystemNode::class, 9)->create();
        //        foreach ($systemNodes as $systemNode) {
        //            $pid = mt_rand(1, 4);
        //            $systemNode->pid = $pid == 2 ?
        //                1 :
        //                $pid;
        //            $systemNode->level = 2;
        //            $systemNode->save();
        //        }
        //
        //        $systemNodes =
        //            factory(\App\Model\Admin\SystemNode::class, 27)->create();
        //        foreach ($systemNodes as $systemNode) {
        //            $systemNode->pid = mt_rand(5, 13);
        //            $systemNode->level = 3;
        //            $systemNode->save();
        //        }
        //
        //        $systemNodes =
        //            factory(\App\Model\Admin\SystemNode::class, 81)->create();
        //        foreach ($systemNodes as $systemNode) {
        //            $systemNode->pid = mt_rand(14, 41);
        //            $systemNode->level = 4;
        //            $systemNode->status = mt_rand(0, 1);
        //            $systemNode->save();
        //        }

        $systemNodes = [
            [
                'name' => '系统',
                'icon' => 'fa fas fa-assistive-listening-systems',
                'children' => [
                    [
                        'name' => '系统首页',
                        'icon' => 'fa fas fa-home',
                        'action' => 'Admin\System\IndexController@index',
                    ],
                    [
                        'name' => '配置中心',
                        'icon' => 'fa fas fa-cogs',
                        'children' => [
                            [
                                'name' => '系统配置',
                                'icon' => 'fa fas fa-cog',
                                'action' => 'Admin\System\IndexController@config',
                            ],
                            [
                                'name' => '资料设置',
                                'icon' => 'fa fas fa-info-circle',
                                'action' => 'Admin\System\IndexController@setInfo',
                            ],
                        ],
                    ],
                    [
                        'name' => '权限功能',
                        'icon' => 'fa fas fa-sitemap',
                        'children' => [
                            [
                                'name' => '账号管理',
                                'icon' => 'fa fas fa-user',
                                'action' => 'Admin\System\UserController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\System\UserController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\System\UserController@edit',
                                    ],
                                    [
                                        'name' => '启用',
                                        'action' => 'Admin\System\UserController@enable',
                                    ],
                                    [
                                        'name' => '禁用',
                                        'action' => 'Admin\System\UserController@disable',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\System\UserController@destroy',
                                    ],
                                ],
                            ],
                            [
                                'name' => '角色管理',
                                'icon' => 'fa fas fa-users',
                                'action' => 'Admin\System\RoleController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\System\RoleController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\System\RoleController@edit',
                                    ],
                                    [
                                        'name' => '启用',
                                        'action' => 'Admin\System\RoleController@enable',
                                    ],
                                    [
                                        'name' => '禁用',
                                        'action' => 'Admin\System\RoleController@disable',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\System\RoleController@destroy',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '地区管理',
                        'icon' => 'fa fas fa-chart-area',
                        'action' => 'Admin\System\AreaController@index',
                        'children' => [
                            [
                                'name' => '排序',
                                'action' => 'Admin\System\AreaController@sort',
                            ],
                        ],
                    ],
                    [
                        'name' => '开发中心',
                        'icon' => 'fa fas fa-code',
                        'children' => [
                            [
                                'name' => '文件管理',
                                'icon' => 'fa fas fa-file',
                                'action' => 'Admin\System\FileController@index',
                                'children' => [
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\System\FileController@destroy',
                                    ],
                                ],
                            ],
                            [
                                'name' => '节点管理',
                                'icon' => 'fa fas fa-bullseye',
                                'action' => 'Admin\System\NodeController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\System\NodeController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\System\NodeController@edit',
                                    ],
                                    [
                                        'name' => '启用',
                                        'action' => 'Admin\System\NodeController@enable',
                                    ],
                                    [
                                        'name' => '禁用',
                                        'action' => 'Admin\System\NodeController@disable',
                                    ],
                                    [
                                        'name' => '排序',
                                        'action' => 'Admin\System\NodeController@sort',
                                    ],
                                    [
                                        'name' => '模块排序',
                                        'action' => 'Admin\System\NodeController@moduleSort',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\System\NodeController@destroy',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'DEMO',
                        'icon' => 'fa fas fa-puzzle-piece',
                        'children' => [
                            [
                                'name' => '单图上传',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@webuploaderImage',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@webuploaderImageSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '多图上传',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@webuploaderImages',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@webuploaderImagesSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '单文件上传',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@webuploaderFile',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@webuploaderFileSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '多文件上传',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@webuploaderFiles',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@webuploaderFilesSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '百度编辑器',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@ueditor',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@ueditorSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '标签',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@tags',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@tagsSave',
                                    ],
                                ],
                            ],
                            [
                                'name' => '查找选择',
                                'icon' => '',
                                'action' => 'Admin\System\DemoController@select2',
                                'children' => [
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\System\NodeController@select2Save',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => '云作品',
                'icon' => 'fa fas fa-cloud',
                'children' => [
                    [
                        'name' => '管理中心',
                        'icon' => 'fa fas fa-sitemap',
                        'children' => [
                            [
                                'name' => '全部用户',
                                'icon' => 'fa fas fa-user',
                                'action' => 'Admin\Works\PhotographerController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\Works\PhotographerController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\Works\PhotographerController@edit',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\Works\PhotographerController@destroy',
                                    ],
                                    [
                                        'name' => '海报',
                                        'action' => 'Admin\Works\PhotographerController@poster',
                                    ],
                                    [
                                        'name' => '图库',
                                        'action' => 'Admin\Works\PhotographerController@gallery',
                                    ],
                                ],
                            ],
                            [
                                'name' => '大咖用户',
                                'icon' => 'fa fas fa-user-secret',
                                'action' => 'Admin\Works\StarController@index',
                                'children' => [
                                    [
                                        'name' => '列表',
                                        'action' => 'Admin\Works\StarController@lists',
                                    ],
                                    [
                                        'name' => '搜索',
                                        'action' => 'Admin\Works\StarController@search',
                                    ],
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\Works\StarController@create',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\Works\StarController@destroy',
                                    ],
                                ],
                            ],
                            [
                                'name' => '全部项目',
                                'icon' => 'fa fas fa-camera',
                                'action' => 'Admin\Works\PhotographerWorkController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\Works\PhotographerWorkController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\Works\PhotographerWorkController@edit',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\Works\PhotographerWorkController@destroy',
                                    ],
                                    [
                                        'name' => '海报',
                                        'action' => 'Admin\Works\PhotographerWorkController@poster',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '营销中心',
                        'icon' => 'fa fa-credit-card',
                        'children' => [
                            [
                                'name' => '海报管理',
                                'icon' => 'fa far fa-file-image',
                                'action' => 'Admin\Works\TemplatesController@index',
                                'children' => [
                                    [
                                        'name' => '列表',
                                        'action' => 'Admin\Works\TemplatesController@lists',
                                    ],
                                    [
                                        'name' => '保存',
                                        'action' => 'Admin\Works\TemplatesController@store',
                                    ],
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\Works\TemplatesController@create',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\Works\TemplatesController@destroy',
                                    ],
                                    [
                                        'name' => '展示',
                                        'action' => 'Admin\Works\TemplatesController@show',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\Works\TemplatesController@edit',
                                    ],
                                    [
                                        'name' => '更新',
                                        'action' => 'Admin\Works\TemplatesController@update',
                                    ],
                                ],
                            ],
                            [
                                'name' => '邀请管理',
                                'icon' => 'fa fas fa-user-plus',
                                'action' => 'Admin\InvoteCode\IndexController@index',
                                'children' => [
                                    [
                                        'name' => '列表',
                                        'action' => 'Admin\InvoteCode\IndexController@lists',
                                    ],
                                    [
                                        'name' => '创建',
                                        'action' => 'Admin\InvoteCode\IndexController@store',
                                    ],
                                    [
                                        'name' => '更新',
                                        'action' => 'Admin\InvoteCode\IndexController@update',
                                    ],
                                ],
                            ],
                            [
                                'name' => '众筹管理',
                                'icon' => 'fas fa-dollar-sign',
                                'action' => 'Admin\CrowdFunding\IndexController@index',
                                'children' => [
                                    [
                                        'name' => '列表',
                                        'action' => 'Admin\CrowdFunding\IndexController@lists',
                                    ],
                                    [
                                        'name' => '更新',
                                        'action' => 'Admin\CrowdFunding\IndexController@store',
                                    ],
                                ],
                            ],
                            [
                                'name' => '众筹历史',
                                'icon' => 'fa fas fa-calendar',
                                'action' => 'Admin\CrowdFundingLog\IndexController@index',
                                'children' => [
                                    [
                                        'name' => '列表',
                                        'action' => 'Admin\CrowdFundingLog\IndexController@lists',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => '设置中心',
                        'icon' => 'fa fas fa-cogs',
                        'children' => [
                            [
                                'name' => '客服信息',
                                'icon' => 'fa fas fa-phone-square',
                                'action' => 'Admin\Works\IndexController@config',
                            ],
                            [
                                'name' => '使用帮助',
                                'icon' => 'fa fas fa-question-circle',
                                'action' => 'Admin\Works\HelpNoteController@index',
                                'children' => [
                                    [
                                        'name' => '添加',
                                        'action' => 'Admin\Works\HelpNoteController@create',
                                    ],
                                    [
                                        'name' => '修改',
                                        'action' => 'Admin\Works\HelpNoteController@edit',
                                    ],
                                    [
                                        'name' => '排序',
                                        'action' => 'Admin\Works\HelpNoteController@sort',
                                    ],
                                    [
                                        'name' => '删除',
                                        'action' => 'Admin\Works\HelpNoteController@destroy',
                                    ],
                                ],
                            ],
                            [
                                'name' => '修改密码',
                                'icon' => 'fa fas fa-key',
                                'action' => 'Admin\System\IndexController@updatePassword',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        \App\Servers\ArrServer::parseData($systemNodes);
    }
}
