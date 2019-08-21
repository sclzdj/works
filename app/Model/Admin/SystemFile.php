<?php

namespace App\Model\Admin;

use App\Servers\FileServer;
use Illuminate\Database\Eloquent\Model;

class SystemFile extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'url',
      'original_url',
      'disk',
      'driver',
      'object',
      'objects',
      'extension',
      'mimeType',
      'size',
      'scene',
      'filename',
      'name',
      'upload_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 图片标签的src  注意特殊场景的不要用它
     * @param     $url  记录url
     * @param int $type 类型 1:水印 2:缩略图 3:缩略图加水印 其它:原图
     *
     * @return string
     */
    static public function src($url,$type=0) {
        if(strpos($url,asset('image_storage?filename='))!==false){
            $driver=self::where('url',$url)->value('driver');
            if($driver){
                if($driver=='local'){
                    if($type==1 || $type==2 || $type==3){
                        return $url.'&type='.$type;
                    }
                }
            }
        }

        return $url;
    }
    /**
     * 删除文件和数据库记录
     *
     * @param string $ids 文件id集合，或单个id都支持
     *
     * @return array
     */
    static public function delFileAndRow($ids) {
        $upload_scenes = config('custom.upload_scenes');
        $result = [];
        $objects = [];
        $systemFiles = self::whereIn('id', is_array($ids) ? $ids : explode(',', $ids))->get();
        foreach ($systemFiles as $systemFile) {
            if (isset($upload_scenes[$systemFile->scene])) {
                foreach ($upload_scenes[$systemFile->scene] as $table => $value) {
                    $where = [];
                    foreach ($value['where'] as $k => $v) {
                        if ($v == 'like') {
                            $where[] = "`{$k}` {$v} '%{$systemFile->url}%'";
                        } elseif ($v == '=' || strtolower($v) == 'eq') {
                            $where[] = "`{$k}` {$v} '{$systemFile->url}'";
                        }
                    }
                    $where = implode(' OR ', $where);
                    $DB = \DB::table($table)->selectRaw('GROUP_CONCAT(`id`) AS `id_str`')->whereRaw($where);
                    if (isset($value['whereRaw']) && $value['whereRaw'] !== '') {
                        $DB->whereRaw($value['whereRaw']);
                    }
                    $id_str = $DB->first()->id_str;
                    if (!$id_str) {
                        $systemFile->delete();
                        $objects[] = $systemFile->objects;
                    } else {
                        if(is_numeric($ids)){
                            $result = [
                              'id'     => $systemFile->id,
                              'table'  => $table,
                              'field'  => implode('|', array_keys($value['where'])),
                              'id_str' => $id_str,
                            ];
                        }else{
                            $result[] = [
                              'id'     => $systemFile->id,
                              'table'  => $table,
                              'field'  => implode('|', array_keys($value['where'])),
                              'id_str' => $id_str,
                            ];
                        }
                    }
                }
            }
        }
        $FileServer = new FileServer();
        foreach ($objects as $object) {
            $FileServer->delete($object);
        }

        return $result;
    }
}
