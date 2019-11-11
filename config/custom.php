<?php

return [
    /*摄影师*/
    'photographer' => [
        'random' => 3,
    ],
    /*微信*/
    'wechat' => [
        'wx' => [//微信

        ],
        'gh' => [//公众号

        ],
        'mp' => [//小程序
            'appid' => 'wxeec7c320c3eb0477',
            'secret' => 'adf5f0ed824672c4a32dda1e44617f6c',
        ],
    ],
    /*七牛*/
    'qiniu' => [
        'accessKey' => '-ME5kiUE5Jha3zH2ipAY89oGSh4sCAacyXpAgFsE',
        'secretKey' => 'Sm_gSAPnP5nlNxuSGBwduFaN4nI5sA4lFGp9vTi-',
        'crop_work_source_image_bg' => 'work_source_image_bg.jpg',
        'avatar' => 'avatar.png',
        'buckets' => [
            'zuopin' => [
                'domain' => 'https://file.zuopin.cloud',
            ],
        ],
    ],
    /*百度*/
    'baidu' => [
        'pan' => [
            'id' => '17131374',
            'apiKey' => '2n959zvKCVgAg0rQ1jiGwSGS',
            'secretKey' => 'T4GvSLtdjuZA64rf4KXsnFCGpLQwxMeY',
        ],
    ],
    /*发送短信*/
    'send_short_message' => [
        'third_type' => 'ali',
        'sms_code' => [
            'expire' => 600,
            'space' => 100,
        ],
        'ali' => [
            'AccessKeyId' => 'LTAI4FpnJmsM8VC6QT7261jc',
            'AccessSecret' => 'IArXhrWYbes76B5O6yYa1llsh6rzNR',
            'RegionId' => 'cn-hangzhou',
            'Scheme' => 'https',
            'TemplateCodes' => [
                /*此为发送验证码场景*/
                'photographer_register' => ['TemplateCode' => 'SMS_174580890', 'SignName' => '云作品'],//摄影师注册场景
                'update_my_photographer_info' => ['TemplateCode' => 'SMS_174580890', 'SignName' => '云作品'],//修改摄影师信息场景

                /*此为发送通知场景*/
                'crowd_raising_result' => ['TemplateCode' => 'SMS_177241298', 'SignName' => '云作品'],//众筹结果通知
                'register_code_generate' => ['TemplateCode' => 'SMS_177251206', 'SignName' => '云作品'],//注册码生成通知
                'register_success' => ['TemplateCode' => 'SMS_177256158', 'SignName' => '云作品'],//注册成功通知
                'service_open' => ['TemplateCode' => 'SMS_177251207', 'SignName' => '云作品'],//服务开启通知
                'visit_remind_1' => ['TemplateCode' => 'SMS_177251208', 'SignName' => '云作品'],//来访提醒1
                'visit_remind_2' => ['TemplateCode' => 'SMS_177256160', 'SignName' => '云作品'],//来访提醒2
                'report_generate' => ['TemplateCode' => 'SMS_177246307', 'SignName' => '云作品'],//报告生成通知
                'silent_activation_1' => ['TemplateCode' => 'SMS_177256162', 'SignName' => '云作品'],//沉默激活提醒1
                'silent_activation_2' => ['TemplateCode' => 'SMS_177256161', 'SignName' => '云作品'],//沉默激活提醒2
                'silent_activation_3' => ['TemplateCode' => 'SMS_177241303', 'SignName' => '云作品'],//沉默激活提醒3
            ],
        ],
    ],


    'upload_image_special_scenes'/*文件上传特殊场景配置，这些场景会在上传时做特殊处理，不会生成水印和缩略图*/ => [
        'set_admin_avatar',
        'set_admin_logo',
        'set_admin_logo_text',
        'set_admin_logo_signin',
        'set_upload_image_watermark',
        'ueditor_upload',
        'ueditor_catch_upload',
    ],
    'upload_scenes'/*文件上传场景配置*/ => [
        //设置管理员头像
        'set_admin_avatar'/*场景名称*/ => [
            'system_users'/*每个场景对应的表，可以多个*/ => [
                'where' => ['avatar' => '='],
                /*表中对应的字段，可以多个，使用OR查询*/
            ],
        ],
        //设置后台logo
        'set_admin_logo' => [
            'system_configs' => [
                'whereRaw' => "`name` = 'admin_logo'",
                /*其它原生查询条件，如果是OR语句，请用()包起来*/
                'where' => ['value' => '='],
            ],
        ],
        //设置后台logo文字
        'set_admin_logo_text' => [
            'system_configs' => [
                'whereRaw' => "`name` = 'admin_logo_text'",
                'where' => ['value' => '='],
            ],
        ],
        //设置后台登录logo
        'set_admin_logo_signin' => [
            'system_configs' => [
                'whereRaw' => "`name` = 'admin_logo_signin'",
                'where' => ['value' => '='],
            ],
        ],
        //设置图片上传水印
        'set_upload_image_watermark' => [
            'system_configs' => [
                'whereRaw' => "`name` = 'upload_image_watermark_pic'",
                'where' => ['value' => '='],
            ],
        ],
        //百度编辑器文件上传
        'ueditor_upload' => [
            'system_demos' => [
                'whereRaw' => "(`name` = 'demo_ueditor_1' OR `name` = 'demo_ueditor_2')",
                'where' => ['value' => 'like'],
            ],
        ],
        //百度编辑器图片远程抓取
        'ueditor_catch_upload' => [
            'system_demos' => [
                'whereRaw' => "(`name` = 'demo_ueditor_1' OR `name` = 'demo_ueditor_2')",
                'where' => ['value' => 'like'],
            ],
        ],
        //demo图片和文件上传
        'demo_webuploader' => [
            'system_demos' => [
                'whereRaw' => "(`name` = 'demo_webuploader_image_1' OR `name` = 'demo_webuploader_image_2' OR `name` = 'demo_webuploader_images_1' OR `name` = 'demo_webuploader_images_2' OR `name` = 'demo_webuploader_file_1' OR `name` = 'demo_webuploader_file_2' OR `name` = 'demo_webuploader_files_1' OR `name` = 'demo_webuploader_files_2')",
                'where' => ['value' => 'like'],
            ],
        ],
    ],
];
