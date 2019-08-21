<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 17:02
 */

namespace App\Servers;

use App\Model\Admin\SystemConfig;
use App\Model\Admin\SystemFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FileServer {

    public $objects = null;

    /**
     * 删除文件存储系统的文件
     *
     * @param $objects 文件对象名集合，可为单个
     */
    public function delete($objects) {
        if (config('filesystems.default') == 'local') {
            if ($objects) {
                $objects = is_array($objects) ? $objects : explode('|', $objects);
                foreach ($objects as $object) {
                    if ($object) {
                        Storage::delete($object);
                    }
                }
            }
        }
    }

    /**
     * @param string $filetype    上传类型，file，image
     * @param string $filename    保存的文件名
     * @param string $path        保存目录
     * @param string $key         表单名称
     * @param string $upload_type 上传类型
     *
     * @return false|string
     */
    public function upload($filetype, $filename, $path = '', $file, $fileInfo = [], $upload_type) {
        if (config('filesystems.default') == 'local') {
            if ($fileInfo['extension'] !== '') {
                $save_filename = $filename.'.'.$fileInfo['extension'];
                if ($filetype == 'image') {
                    $save_watermark_filename = $filename.'_watermark.'.$fileInfo['extension'];
                    $save_thumb_filename = $filename.'_thumb.'.$fileInfo['extension'];
                    $save_watermark_thumb_filename = $filename.'_watermark_thumb.'.$fileInfo['extension'];
                }
            } else {
                $save_filename = $filename;
                if ($filetype == 'image') {
                    $save_watermark_filename = $filename.'_watermark';
                    $save_thumb_filename = $filename.'_thumb';
                    $save_watermark_thumb_filename = $filename.'_watermark_thumb';
                }
            }
            $systemFile = SystemFile::where(['filename' => $filename])->first();
            if (!$systemFile) {
                $systemFile = SystemFile::create();
            } else {
                //先删除原来的缩略图和水印图
                $this->delete($systemFile->objects);
            }
            if ($fileInfo['scene'] == 'ueditor_catch_upload') {
                $object = 'public/'.$path.'/'.$save_filename;
            } else {
                $object = $file->storeAs('public/'.$path, $save_filename);
            }
            $objects = [];
            $objects[] = $object;
            if (($upload_type == 'image' || $upload_type == 'images') && $filetype == 'image') {
                if (!is_dir('storage')) {
                    return false;
                }
                $newpath = 'storage/'.$path;
                $upload_editor_image_watermark_on = SystemConfig::getVal('upload_editor_image_watermark_on', 'upload');
                $upload_editor_catch_image_watermark_on = SystemConfig::getVal('upload_editor_catch_image_watermark_on', 'upload');
                $upload_image_watermark_on = SystemConfig::getVal('upload_image_watermark_on', 'upload');//水印开关
                $upload_image_watermark_pic = SystemConfig::getVal('upload_image_watermark_pic', 'upload');//水印图片
                if ($upload_image_watermark_pic === '') {
                    $upload_image_watermark_pic = 'static/admin/img/watermark.png';
                }
                $upload_image_watermark_position = SystemConfig::getVal('upload_image_watermark_position', 'upload');//水印位置
                $upload_image_watermark_position = $upload_image_watermark_position === '' ? 'bottom-right' : $upload_image_watermark_position;
                $marginX = 5;
                $marginY = 5;
                $water = Image::make($upload_image_watermark_pic);
                $bwWidth = 2 * ($water->width() + $marginX * 2);
                $bwHeight = 2 * ($water->height() + $marginY * 2);
                if (in_array($fileInfo['scene'], config('custom.upload_image_special_scenes'))) {
                    if ($fileInfo['scene'] == 'set_upload_image_watermark') {
                        $img = Image::make($file);
                        if ($img->width() > 100 || $img->height() > 100) {//把上传的水印图片等比缩小到100px以下
                            $img->resize(100, 100, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            }
                            )->save($newpath.'/'.$save_filename);
                        }
                    } elseif ($fileInfo['scene'] == 'ueditor_upload') {
                        //生成水印
                        $img = Image::make($file);
                        $watermark_status = $img->width() >= $bwWidth && $img->height() >= $bwHeight;
                        if ($upload_editor_image_watermark_on == 1 && $watermark_status) {
                            $img->insert($upload_image_watermark_pic, $upload_image_watermark_position, $marginX, $marginY)->save($newpath.'/'.$save_filename);
                        }
                    } elseif ($fileInfo['scene'] == 'ueditor_catch_upload') {
                        //生成水印
                        $img = Image::make($file);
                        $watermark_status = $img->width() >= $bwWidth && $img->height() >= $bwHeight;
                        if ($upload_editor_catch_image_watermark_on == 1 && $watermark_status) {
                            $img->insert($upload_image_watermark_pic, $upload_image_watermark_position, $marginX, $marginY)->save($newpath.'/'.$save_filename);
                        } else {
                            $arrTmp = explode('/', $newpath.'/'.$save_filename);
                            array_pop($arrTmp);
                            $newDir = implode('/', $arrTmp);
                            if (!is_dir($newDir)) {
                                mkdir($newDir, 0777, true);
                            }
                            $img->save($newpath.'/'.$save_filename);
                        }
                    }
                    $url = asset(Storage::url($object));//原始文件
                } else {
                    $gbImg = Image::make($file);
                    //生成水印
                    $img = Image::make($file);
                    $watermark_status = $img->width() >= $bwWidth && $img->height() >= $bwHeight;
                    if ($upload_image_watermark_on == 1 && $watermark_status) {
                        $img->insert($upload_image_watermark_pic, $upload_image_watermark_position, $marginX, $marginY)->save($newpath.'/'.$save_watermark_filename);
                        $gbImg->insert($upload_image_watermark_pic, $upload_image_watermark_position, $marginX, $marginY);
                    } else {
                        $img->save($newpath.'/'.$save_watermark_filename);
                    }
                    $objects[] = 'public/'.$path.'/'.$save_watermark_filename;
                    //生成缩略图
                    $upload_image_thumb_on = SystemConfig::getVal('upload_image_thumb_on', 'upload');//水印开关
                    $upload_image_thumb_size = SystemConfig::getVal('upload_image_thumb_size', 'upload');//水印图片
                    if ($upload_image_thumb_size !== '') {
                        $upload_image_thumb_size = explode('*', $upload_image_thumb_size);
                    } else {
                        $upload_image_thumb_size = [
                          200,
                          200,
                        ];
                    }
                    $img = Image::make($file);
                    $thumb_status = $img->width() > $upload_image_thumb_size[0] || $img->height() > $upload_image_thumb_size[1];
                    if ($upload_image_thumb_on == 1 && $thumb_status) {
                        $img->resize($upload_image_thumb_size[0], $upload_image_thumb_size[1], function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        }
                        )->save($newpath.'/'.$save_thumb_filename);
                        $gbImg->resize($upload_image_thumb_size[0], $upload_image_thumb_size[1], function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        }
                        );
                    } else {
                        $img->save($newpath.'/'.$save_thumb_filename);
                    }
                    $objects[] = 'public/'.$path.'/'.$save_thumb_filename;
                    $gbImg->save($newpath.'/'.$save_watermark_thumb_filename);
                    $objects[] = 'public/'.$path.'/'.$save_watermark_thumb_filename;
                    $url = asset('image_storage?filename='.urlencode('storage/'.$path.'/'.$filename).'&extension='.$fileInfo['extension']);
                }
            } else {
                $url = asset(Storage::url($object));//原始文件
            }
            $this->objects = $objects;
            $update = [
              'url'          => $url,
              'original_url' => asset(Storage::url($object)),
              'disk'         => config('filesystems.default'),
              'driver'       => config('filesystems.disks.'.
                config('filesystems.default').'.driver'
              ),
              'object'       => $object,
              'objects'      => implode('|', $objects),
              'filename'     => $filename,
              'upload_type'  => $upload_type,
              'name'         => $fileInfo['extension'] === '' ? str_replace('/', '_', $filename) : str_replace('/', '_', $filename).'.'.$fileInfo['extension'],
            ];
            $update = array_merge($update, $fileInfo);
            $systemFile->update($update);

            return $url;
        } else {
            return false;
        }
    }
}
