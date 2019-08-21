<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Admin\SystemConfig;
use App\Model\Admin\SystemFile;
use App\Servers\FileServer;
use Illuminate\Http\Request;

class FileController extends BaseController {

    public function index(Request $request) {
        $scenes = SystemFile::select('scene')->where('scene', '<>', '')->distinct()->get()->toArray();//查出所有场景
        $pageInfo = [
          'pageSize' => $request['pageSize'] !== null ?
            $request['pageSize'] :
            SystemConfig::getVal('basic_page_size'),
          'page'     => $request['page'] !== null ?
            $request['page'] :
            1,
        ];

        $filter = [
          'url'              => $request['url'] !== null ?
            $request['url'] :
            '',
          'driver'           => $request['driver'] !== null ?
            $request['driver'] :
            '',
          'disk'             => $request['disk'] !== null ?
            $request['disk'] :
            '',
          'scene'            => $request['scene'] !== null ?
            $request['scene'] :
            '',
          'mimeType'         => $request['mimeType'] !== null ?
            $request['mimeType'] :
            '',
          'created_at_start' => $request['created_at_start'] !== null ?
            $request['created_at_start'] :
            '',
          'created_at_end'   => $request['created_at_end'] !== null ?
            $request['created_at_end'] :
            '',
        ];
        $orderBy = [
          'order_field' => $request['order_field'] !== null ?
            $request['order_field'] :
            'id',
          'order_type'  => $request['order_type'] !== null ?
            $request['order_type'] :
            'desc',
        ];
        $where = [];
        if ($filter['url'] !== '') {
            $where[] = [
              'url',
              'like',
              '%'.$filter['url'].'%',
            ];
        }
        if ($filter['driver'] !== '') {
            $where[] = [
              'driver',
              '=',
              $filter['driver'],
            ];
        }
        if ($filter['disk'] !== '') {
            $where[] = [
              'disk',
              '=',
              $filter['disk'],
            ];
        }
        if ($filter['scene'] !== '') {
            $where[] = [
              'scene',
              '=',
              $filter['scene'],
            ];
        }
        if ($filter['mimeType'] !== '') {
            $where[] = [
              'mimeType',
              'like',
              '%'.$filter['mimeType'].'%',
            ];
        }
        if ($filter['created_at_start'] !== ''
          && $filter['created_at_end'] !== ''
        ) {
            $where[] = [
              'created_at',
              '>=',
              $filter['created_at_start']." 00:00:00",
            ];
            $where[] = [
              'created_at',
              '<=',
              $filter['created_at_end']." 23:59:59",
            ];
        } elseif ($filter['created_at_start'] === ''
          && $filter['created_at_end'] !== ''
        ) {
            $where[] = [
              'created_at',
              '<=',
              $filter['created_at_end']." 23:59:59",
            ];
        } elseif ($filter['created_at_start'] !== ''
          && $filter['created_at_end'] === ''
        ) {
            $where[] = [
              'created_at',
              '>=',
              $filter['created_at_start']." 00:00:00",
            ];
        }
        $systemFiles = SystemFile::where($where)
                                 ->orderBy($orderBy['order_field'], $orderBy['order_type'])
                                 ->paginate($pageInfo['pageSize']);

        return view('/admin/system/file/index',
          compact('systemFiles', 'pageInfo', 'orderBy', 'filter', 'scenes')
        );
    }

    public function destroy(Request $request) {
        $id = $request->id;
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                $result = SystemFile::delFileAndRow($id);
                \DB::commit();//提交事务
                if ($result) {
                    return $this->response('<div>有关联数据，不能删除！关联数据：<li>表:'.$result['table'].'，字段:'.$result['field'].'，关联记录:'.$result['id_str'].'</li></div>', 400);
                }

                return $this->response('删除成功', 200);
            } else {
                $ids = is_array($request->ids) ?
                  $request->ids :
                  explode(',', $request->ids);
                $result = SystemFile::delFileAndRow($ids);
                \DB::commit();//提交事务
                if ($result) {
                    if (count($result) === count($ids)) {
                        $message = '<div>所选数据全都有关联数据，不能删除！关联数据：';
                        foreach ($result as $r) {
                            $message .= '<li>记录:'.$r['id'].'，表'.$r['table'].'，字段:'.$r['field'].'，关联记录:'.$r['id_str'].'</li>';
                        }
                        $message .= '</div>';

                        return $this->response($message, 400);
                    }
                    $message = '<div><span style="color: #c54736;">以下记录有关联数据，不能删除</span>，其它已批量删除成功！<div><span style="color: #c54736;">有关联数据记录：</span>';
                    foreach ($result as $r) {
                        $message .= '<li style="color: #c54736;">记录:'.$r['id'].'，表'.$r['table'].'，字段:'.$r['field'].'，关联记录:'.$r['id_str'].'</li>';
                    }
                    $message .= '</div>';
                } else {
                    $message = '批量删除成功';
                }

                return $this->response($message, 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 文件上传
     *
     * @param string $path 保存目录
     * @param string $key  表单名称
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, $path = 'uploads', $key = 'file') {
        $upload_type = (string)$request->instance()->post('upload_type', (string)$request->instance()->get('upload_type', 'file'));
        $filename = (string)$request->instance()->post('filename', urldecode((string)$request->instance()->get('filename', '')));
        $scene = (string)$request->instance()->post('scene', (string)$request->instance()->get('scene', ''));
        $filename = ltrim(str_replace('\\', '/', $filename), '/');
        $upload_scenes = config('custom.upload_scenes');
        if (!isset($upload_scenes[$scene])) {
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => '该场景值没有被配置预设，无效',
                  ]
                );
            }

            return $this->uploadResponse('该场景值没有被配置预设，无效', 400);
        }
        if ($filename === ''
          || in_array($upload_type, [
              'images',
              'files',
            ]
          )) {
            $filename = date("Ymd/").time().mt_rand(10000, 99999);
        }
        if (!$request->file($key)) {
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => '没有选择上传文件',
                  ]
                );
            }

            return $this->uploadResponse('没有选择上传文件', 400);
        }
        $iniSize = $request->file($key)->getMaxFilesize();
        if (!$request->hasFile($key)) {
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => 'php.ini最大限制上传'.
                      number_format($iniSize /
                        1024 / 1024, 2, '.',
                        ''
                      ).'M的文件',
                  ]
                );
            }

            return $this->uploadResponse('php.ini最大限制上传'.
              number_format($iniSize /
                1024 / 1024, 2, '.',
                ''
              ).'M的文件', 400
            );
        }
        if (!$request->file($key)->isValid()) {
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => '上传过程中出错，请主要检查php.ini是否配置正确',
                  ]
                );
            }

            return $this->uploadResponse('上传过程中出错，请主要检查php.ini是否配置正确', 400);
        }
        $fileInfo = [];
        $fileInfo['extension'] = $request->file($key)->clientExtension() !== '' ? $request->file($key)->clientExtension() : $request->file($key)->extension();
        $fileInfo['mimeType'] = $request->file($key)->getMimeType();
        $fileInfo['size'] = $request->file($key)->getSize();
        $fileInfo['iniSize'] = $iniSize;
        if ($fileInfo['size'] > $fileInfo['iniSize']) {
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => 'php.ini最大限制上传'.
                      number_format($fileInfo['iniSize'] /
                        1024 / 1024, 2, '.',
                        ''
                      ).'M的文件',
                  ]
                );
            }

            return $this->uploadResponse('php.ini最大限制上传'.
              number_format($fileInfo['iniSize'] /
                1024 / 1024, 2, '.',
                ''
              ).'M的文件', 400
            );
        }
        if ($scene == '这里写你要判断的场景') {//这里是上传场景可以根据这个做一些特殊判断，下面写出对应的限制即可
            $upload_image_limit_size = '';
            $upload_image_allow_extension = '';
            $upload_file_limit_size = '';
            $upload_file_allow_extension = '';
        }
        $filetype = 'file';
        if (strpos($fileInfo['mimeType'], 'image/') !== false) {
            $filetype = 'image';
            $upload_image_limit_size = $upload_image_limit_size ?? SystemConfig::getVal('upload_image_limit_size', 'upload');
            if ($upload_image_limit_size > 0
              && $fileInfo['size'] > $upload_image_limit_size * 1000
            ) {
                if ($scene == 'ueditor_upload') {
                    return response()->json([
                        "state" => '最大允许上传'.
                          $upload_image_limit_size.'K的图片',
                      ]
                    );
                }

                return $this->uploadResponse('最大允许上传'.
                  $upload_image_limit_size.'K的图片',
                  400
                );
            }
            $upload_image_allow_extension = $upload_image_allow_extension ?? SystemConfig::getVal('upload_image_allow_extension', 'upload');
            if ($upload_image_allow_extension !== '') {
                $upload_image_allow_extension_arr =
                  explode(',', $upload_image_allow_extension);
                if (!in_array($fileInfo['extension'],
                  $upload_image_allow_extension_arr
                )
                ) {
                    if ($scene == 'ueditor_upload') {
                        return response()->json([
                            "state" => '只允许上传图片的后缀类型：'.
                              $upload_image_allow_extension,
                          ]
                        );
                    }

                    return $this->uploadResponse('只允许上传图片的后缀类型：'.
                      $upload_image_allow_extension,
                      400
                    );
                }
            }
        } else {
            $upload_file_limit_size = $upload_file_limit_size ?? SystemConfig::getVal('upload_file_limit_size', 'upload');
            if ($upload_file_limit_size > 0
              && $fileInfo['size'] > $upload_file_limit_size * 1000
            ) {
                if ($scene == 'ueditor_upload') {
                    return response()->json([
                        "state" => '最大允许上传'.
                          $upload_file_limit_size.'K的文件',
                      ]
                    );
                }

                return $this->uploadResponse('最大允许上传'.
                  $upload_file_limit_size.'K的文件',
                  400
                );
            }
            $upload_file_allow_extension = $upload_file_allow_extension ?? SystemConfig::getVal('upload_file_allow_extension', 'upload');
            if ($upload_file_allow_extension !== '') {
                $upload_file_allow_extension_arr =
                  explode(',', $upload_file_allow_extension);
                if (!in_array($fileInfo['extension'],
                  $upload_file_allow_extension_arr
                )
                ) {
                    if ($scene == 'ueditor_upload') {
                        return response()->json([
                            "state" => "只允许上传文件的后缀类型",
                          ]
                        );
                    }

                    return $this->uploadResponse('只允许上传文件的后缀类型：'.
                      $upload_file_allow_extension,
                      400
                    );
                }
            }
        }
        $fileInfo['scene'] = $scene;
        \DB::beginTransaction();//开启事务
        $FileServer = new FileServer();
        try {
            if (request()->method() == 'OPTIONS') {
                return $this->response([]);
            }

            $url = $FileServer->upload($filetype, $filename, $path, $request->file($key), $fileInfo, $upload_type);
            if ($url !== false) {
                \DB::commit();//提交事务
                if ($scene == 'ueditor_upload') {
                    return response()->json([
                        "state"    => "SUCCESS",
                        "url"      => $url,
                        "title"    => $url,
                        "original" => $url,
                      ]
                    );
                }

                return $this->uploadResponse('上传成功', 201, ['url' => $url]);
            } else {
                \DB::rollback();//回滚事务
                $FileServer->delete($FileServer->objects);
                if ($scene == 'ueditor_upload') {
                    return response()->json([
                        "state" => "上传失败",
                      ]
                    );
                }

                return $this->uploadResponse('上传失败', 400);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务
            $FileServer->delete($FileServer->objects);
            if ($scene == 'ueditor_upload') {
                return response()->json([
                    "state" => $e->getMessage(),
                  ]
                );
            }

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 百度编辑器抓取图片
     *
     * @param \Illuminate\Http\Request $request
     */
    public function ueditorCatchImage(Request $request, $path = 'uploads') {
        $scene = (string)$request->instance()->post('scene', (string)$request->instance()->get('scene', ''));
        $upload_scenes = config('custom.upload_scenes');
        if (!isset($upload_scenes[$scene])) {
            return $this->uploadResponse('该场景值没有被配置预设，无效', 400);
        }
        $source = $request->source;
        $imgs = [];
        foreach ($source as $s) {
            $info = get_headers($s, true);
            if (strpos($info['Content-Type'], 'image/') !== false) {
                $fileInfo = [];
                $mimeTypeArr = explode('/', $info['Content-Type']);
                $fileInfo['extension'] = $mimeTypeArr[count($mimeTypeArr) - 1];
                $fileInfo['mimeType'] = $info['Content-Type'];
                $fileInfo['size'] = $info['Content-Length'];
                $fileInfo['scene'] = $scene;
                $upload_image_limit_size = SystemConfig::getVal('upload_image_limit_size', 'upload');
                if ($upload_image_limit_size > 0
                  && $fileInfo['size'] > $upload_image_limit_size * 1000
                ) {
                    continue;
                }
                $upload_image_allow_extension = SystemConfig::getVal('upload_image_allow_extension', 'upload');
                if ($upload_image_allow_extension !== '') {
                    $upload_image_allow_extension_arr =
                      explode(',', $upload_image_allow_extension);
                    if (!in_array($fileInfo['extension'],
                      $upload_image_allow_extension_arr
                    )
                    ) {
                        continue;
                    }
                }
                $imgs[] = [
                  'source'   => $s,
                  'fileInfo' => $fileInfo,
                ];
            }
        }
        $state = 'SUCCESS';
        $list = [];
        foreach ($imgs as $img) {
            \DB::beginTransaction();//开启事务
            $FileServer = new FileServer();
            try {
                if (request()->method() == 'OPTIONS') {
                    return $this->response([]);
                }
                $url = $FileServer->upload('image', date("Ymd/").time().mt_rand(10000, 99999), $path, $img['source'], $img['fileInfo']);

                if ($url !== false) {
                    \DB::commit();//提交事务
                    $list[] = [
                      'url'    => $url,
                      'source' => $img['source'],
                      'state'  => $state,
                    ];
                } else {
                    \DB::rollback();//回滚事务
                    $FileServer->delete($FileServer->objects);
                }
            } catch (\Exception $e) {
                \DB::rollback();//回滚事务
                $FileServer->delete($FileServer->objects);
            }
        }

        return response()->json(compact('state', 'list'))->setCallback(request()->input('callback'));
    }

    /**
     * 百度编辑器在线选择图片或文件
     *
     * @param \Illuminate\Http\Request $request
     */
    public function ueditorList(Request $request) {
        $type = $request->instance()->get('type', 'file');
        $start = (int)$request->start;
        $size = (int)$request->size;
        $SystemFile = SystemFile::select('url')->whereIn('scene', [
            'ueditor_upload',
            'ueditor_catch_upload',
          ]
        );
        if ($type == 'image') {
            $SystemFile = $SystemFile->where('mimeType', 'like', '%image/%');
            $upload_image_limit_size = SystemConfig::getVal('upload_image_limit_size', 'upload');
            $upload_image_allow_extension = SystemConfig::getVal('upload_image_allow_extension', 'upload');
            if ($upload_image_limit_size > 0) {
                $SystemFile = $SystemFile->where('size', '<=', $upload_image_limit_size * 1000);
            }
            if ($upload_image_allow_extension !== '') {
                $SystemFile = $SystemFile->whereIn('extension', explode(',', $upload_image_allow_extension));
            }
        } else {
            $upload_file_limit_size = SystemConfig::getVal('upload_file_limit_size', 'upload');
            $upload_file_allow_extension = SystemConfig::getVal('upload_file_allow_extension', 'upload');
            if ($upload_file_limit_size > 0) {
                $SystemFile = $SystemFile->where('size', '<=', $upload_file_limit_size * 1000);
            }
            if ($upload_file_allow_extension !== '') {
                $SystemFile = $SystemFile->whereIn('extension', explode(',', $upload_file_allow_extension));
            }
        }
        $SystemFileBak = $SystemFile;
        $total = $SystemFileBak->count();
        $list = $SystemFile->offset($start)->limit($size)->get()->toArray();
        $state = 'SUCCESS';

        return response()->json(compact('state', 'list', 'start', 'total'))->setCallback(request()->input('callback'));
    }

    /**
     * 百度编辑器配置
     */
    public function ueditorUploadConfig() {
        //http://fex.baidu.com/ueditor/#server-config官方文档
        $upload_image_limit_size = SystemConfig::getVal('upload_image_limit_size', 'upload');
        $upload_image_allow_extension = SystemConfig::getVal('upload_image_allow_extension', 'upload');
        $upload_file_limit_size = SystemConfig::getVal('upload_file_limit_size', 'upload');
        $upload_file_allow_extension = SystemConfig::getVal('upload_file_allow_extension', 'upload');

        return response()->json([
            'imageActionName'         => 'uploadimage',
            'imageFieldName'          => 'file',
            'imageMaxSize'            => $upload_image_limit_size > 0 ? $upload_image_limit_size * 1000 : 2048000,
            'imageAllowFiles'         => $upload_image_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_image_allow_extension)
            ) : [
              0 => '.png',
              1 => '.jpg',
              2 => '.jpeg',
              3 => '.gif',
              4 => '.bmp',
            ],
            'imageCompressEnable'     => true,
            'imageCompressBorder'     => 1600,
            'imageInsertAlign'        => 'none',
            'imageUrlPrefix'          => '',
            'imagePathFormat'         => '/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlActionName'        => 'uploadscrawl',
            'scrawlFieldName'         => 'file',
            'scrawlPathFormat'        => '/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlMaxSize'           => $upload_image_limit_size > 0 ? $upload_image_limit_size * 1000 : 2048000,
            'scrawlUrlPrefix'         => '',
            'scrawlInsertAlign'       => 'none',
            'snapscreenActionName'    => 'uploadimage',
            'snapscreenPathFormat'    => '/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'snapscreenUrlPrefix'     => '',
            'snapscreenInsertAlign'   => 'none',
            'catcherLocalDomain'      => [
              0 => '127.0.0.1',
              1 => 'localhost',
              2 => $_SERVER['HTTP_HOST'],
            ],
            'catcherActionName'       => 'catchimage',
            'catcherFieldName'        => 'source',
            'catcherPathFormat'       => '/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'catcherUrlPrefix'        => '',
            'catcherMaxSize'          => $upload_image_limit_size > 0 ? $upload_image_limit_size * 1000 : 2048000,
            'catcherAllowFiles'       => $upload_image_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_image_allow_extension)
            ) : [
              0 => '.png',
              1 => '.jpg',
              2 => '.jpeg',
              3 => '.gif',
              4 => '.bmp',
            ],
            'videoActionName'         => 'uploadvideo',
            'videoFieldName'          => 'file',
            'videoPathFormat'         => '/ueditor/php/upload/video/{yyyy}{mm}{dd}/{time}{rand:6}',
            'videoUrlPrefix'          => '',
            'videoMaxSize'            => $upload_file_limit_size > 0 ? $upload_file_limit_size * 1000 : 102400000,
            'videoAllowFiles'         => $upload_file_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_file_allow_extension)
            ) : [
              0  => '.flv',
              1  => '.swf',
              2  => '.mkv',
              3  => '.avi',
              4  => '.rm',
              5  => '.rmvb',
              6  => '.mpeg',
              7  => '.mpg',
              8  => '.ogg',
              9  => '.ogv',
              10 => '.mov',
              11 => '.wmv',
              12 => '.mp4',
              13 => '.webm',
              14 => '.mp3',
              15 => '.wav',
              16 => '.mid',
            ],
            'fileActionName'          => 'uploadfile',
            'fileFieldName'           => 'file',
            'filePathFormat'          => '/ueditor/php/upload/file/{yyyy}{mm}{dd}/{time}{rand:6}',
            'fileUrlPrefix'           => '',
            'fileMaxSize'             => $upload_file_limit_size > 0 ? $upload_file_limit_size * 1000 : 51200000,
            'fileAllowFiles'          => $upload_file_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_file_allow_extension)
            ) : [
              0  => '.png',
              1  => '.jpg',
              2  => '.jpeg',
              3  => '.gif',
              4  => '.bmp',
              5  => '.flv',
              6  => '.swf',
              7  => '.mkv',
              8  => '.avi',
              9  => '.rm',
              10 => '.rmvb',
              11 => '.mpeg',
              12 => '.mpg',
              13 => '.ogg',
              14 => '.ogv',
              15 => '.mov',
              16 => '.wmv',
              17 => '.mp4',
              18 => '.webm',
              19 => '.mp3',
              20 => '.wav',
              21 => '.mid',
              22 => '.rar',
              23 => '.zip',
              24 => '.tar',
              25 => '.gz',
              26 => '.7z',
              27 => '.bz2',
              28 => '.cab',
              29 => '.iso',
              30 => '.doc',
              31 => '.docx',
              32 => '.xls',
              33 => '.xlsx',
              34 => '.ppt',
              35 => '.pptx',
              36 => '.pdf',
              37 => '.txt',
              38 => '.md',
              39 => '.xml',
            ],
            'imageManagerActionName'  => 'listimage',
            'imageManagerListPath'    => '/ueditor/php/upload/image/',
            'imageManagerListSize'    => 20,
            'imageManagerUrlPrefix'   => '',
            'imageManagerInsertAlign' => 'none',
            'imageManagerAllowFiles'  => $upload_image_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_image_allow_extension)
            ) : [
              0 => '.png',
              1 => '.jpg',
              2 => '.jpeg',
              3 => '.gif',
              4 => '.bmp',
            ],
            'fileManagerActionName'   => 'listfile',
            'fileManagerListPath'     => '/ueditor/php/upload/file/',
            'fileManagerUrlPrefix'    => '',
            'fileManagerListSize'     => 20,
            'fileManagerAllowFiles'   => $upload_file_allow_extension !== '' ? array_map(function ($v) {
                return '.'.$v;
            }, explode(',', $upload_file_allow_extension)
            ) : [
              0  => '.png',
              1  => '.jpg',
              2  => '.jpeg',
              3  => '.gif',
              4  => '.bmp',
              5  => '.flv',
              6  => '.swf',
              7  => '.mkv',
              8  => '.avi',
              9  => '.rm',
              10 => '.rmvb',
              11 => '.mpeg',
              12 => '.mpg',
              13 => '.ogg',
              14 => '.ogv',
              15 => '.mov',
              16 => '.wmv',
              17 => '.mp4',
              18 => '.webm',
              19 => '.mp3',
              20 => '.wav',
              21 => '.mid',
              22 => '.rar',
              23 => '.zip',
              24 => '.tar',
              25 => '.gz',
              26 => '.7z',
              27 => '.bz2',
              28 => '.cab',
              29 => '.iso',
              30 => '.doc',
              31 => '.docx',
              32 => '.xls',
              33 => '.xlsx',
              34 => '.ppt',
              35 => '.pptx',
              36 => '.pdf',
              37 => '.txt',
              38 => '.md',
              39 => '.xml',
            ],
          ]
        );
    }

    /**
     * 图片显示专属方法
     *
     * @param \Illuminate\Http\Request $request
     */
    public function image(Request $request) {
        $filename = urldecode($request->instance()->get('filename', ''));
        $extension = $request->instance()->get('extension', '');
        if ($filename === '') {
            abort(422, '缺少图片名');
        }
        $type = $request->instance()->get('type', 0);//1:水印 2:缩略图 3:缩略图加水印 其它:原图
        if ($extension !== '') {
            $save_filename = $filename.'.'.$extension;
            $save_watermark_filename = $filename.'_watermark.'.$extension;
            $save_thumb_filename = $filename.'_thumb.'.$extension;
            $save_watermark_thumb_filename = $filename.'_watermark_thumb.'.$extension;
        } else {
            $save_filename = $filename;
            $save_watermark_filename = $filename.'_watermark';
            $save_thumb_filename = $filename.'_thumb';
            $save_watermark_thumb_filename = $filename.'_watermark_thumb';
        }
        $img = $save_filename;
        if ($type == 1) {
            if (is_file($save_watermark_filename)) {
                $img = $save_watermark_filename;
            }
        } elseif ($type == 2) {
            if (is_file($save_thumb_filename)) {
                $img = $save_thumb_filename;
            }
        } elseif ($type == 3) {
            if (is_file($save_watermark_thumb_filename)) {
                $img = $save_watermark_thumb_filename;
            }
        }
        if (!is_file($img)) {
            abort(404, '未找到图片');
        }
        $finfo = finfo_open(FILEINFO_MIME);
        $mime = finfo_file($finfo, $img);
        finfo_close($finfo);
        if (strpos($mime, 'image/') === false) {
            abort(404, '未找到图片');
        }
        $imagecreatefromfunction = 'imagecreatefrom'.strtolower(str_replace('image/', '', $mime));
        $imagefunction = 'image'.strtolower(str_replace('image/', '', $mime));
        if (!function_exists($imagecreatefromfunction) || !function_exists($imagefunction)) {
            header('Content-Type:'.$mime);
            echo file_get_contents($img);
        } else {
            $image = $imagecreatefromfunction($img);
            ob_start();
            $imagefunction($image);
            $content = ob_get_clean();
            imagedestroy($image);

            return response($content, 200, [
                'Content-Length' => strlen($content),
                'Content-Type'   => $mime,
              ]
            );
        }
    }
}
