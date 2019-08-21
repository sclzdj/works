<?php

return [
  'upload_image_special_scenes'/*文件上传特殊场景配置，这些场景会在上传时做特殊处理，不会生成水印和缩略图*/ => [
    'set_admin_avatar',
    'set_admin_logo',
    'set_admin_logo_text',
    'set_admin_logo_signin',
    'set_upload_image_watermark',
    'ueditor_upload',
    'ueditor_catch_upload',
  ],
  'upload_scenes'/*文件上传场景配置*/                                     => [
      //设置管理员头像
      'set_admin_avatar'/*场景名称*/   => [
        'system_users'/*每个场景对应的表，可以多个*/ => [
          'where' => ['avatar' => '='],
          /*表中对应的字段，可以多个，使用OR查询*/
        ],
      ],
      //设置后台logo
      'set_admin_logo'             => [
        'system_configs' => [
          'whereRaw' => "`name` = 'admin_logo'",
          /*其它原生查询条件，如果是OR语句，请用()包起来*/
          'where'    => ['value' => '='],
        ],
      ],
      //设置后台logo文字
      'set_admin_logo_text'        => [
        'system_configs' => [
          'whereRaw' => "`name` = 'admin_logo_text'",
          'where'    => ['value' => '='],
        ],
      ],
      //设置后台登录logo
      'set_admin_logo_signin'      => [
        'system_configs' => [
          'whereRaw' => "`name` = 'admin_logo_signin'",
          'where'    => ['value' => '='],
        ],
      ],
      //设置图片上传水印
      'set_upload_image_watermark' => [
        'system_configs' => [
          'whereRaw' => "`name` = 'upload_image_watermark_pic'",
          'where'    => ['value' => '='],
        ],
      ],
      //百度编辑器文件上传
      'ueditor_upload'             => [
        'system_demos' => [
          'whereRaw' => "(`name` = 'demo_ueditor_1' OR `name` = 'demo_ueditor_2')",
          'where'    => ['value' => 'like'],
        ],
      ],
      //百度编辑器图片远程抓取
      'ueditor_catch_upload'       => [
        'system_demos' => [
          'whereRaw' => "(`name` = 'demo_ueditor_1' OR `name` = 'demo_ueditor_2')",
          'where'    => ['value' => 'like'],
        ],
      ],
      //demo图片和文件上传
      'demo_webuploader'           => [
        'system_demos' => [
          'whereRaw' => "(`name` = 'demo_webuploader_image_1' OR `name` = 'demo_webuploader_image_2' OR `name` = 'demo_webuploader_images_1' OR `name` = 'demo_webuploader_images_2' OR `name` = 'demo_webuploader_file_1' OR `name` = 'demo_webuploader_file_2' OR `name` = 'demo_webuploader_files_1' OR `name` = 'demo_webuploader_files_2')",
          'where'    => ['value' => 'like'],
        ],
      ],
  ],
];