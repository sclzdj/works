<?php

namespace App\Servers;

use App\Model\Admin\SystemArea;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkImg;
use App\Model\Index\SmsCode;

class SystemServer
{
    /**
     * 验证短信验证码
     * @param $mobile 手机号
     * @param $code 验证码
     * @param $purpose 用途
     * @param $ip IP
     * @return array
     */
    public static function verifySmsCode($mobile, $code, $purpose, $ip)
    {
        $sms_code = SmsCode::where(
            ['mobile' => $mobile, 'code' => $code, 'purpose' => $purpose, 'ip' => $ip, 'is_used' => 0]
        )->orderBy('created_at', 'desc')->first();
        if ($sms_code) {
            if (strtotime($sms_code->expired_at) < time()) {
                return ['status' => 'ERROR', 'message' => '短信验证码已过期，请重新发送'];
            } else {
                $sms_code->is_used = 1;
                $sms_code->save();

                return ['status' => 'SUCCESS', 'message' => 'OK'];
            }
        } else {
            return ['status' => 'ERROR', 'message' => '短信验证码错误'];
        }
    }

    /**
     * 解析数据中的地区名称
     * @param $data 数据
     * @param bool $px
     * @return array
     */
    public static function parseRegionName($data, $px = true)
    {
        if (!is_array($data) && $px) {
            return $data;
        }
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parseRegionName($v, false);
            } else {
                $temp = [
                    'id' => $v,
                    'name' => '',
                    'short_name' => '',
                ];
                if ($k == 'province') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['province'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['province'] = $temp;
                    }
                } elseif ($k == 'city') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['city'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['city'] = $temp;
                    }
                } elseif ($k == 'area') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['area'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['area'] = $temp;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 格式化接口分页数据
     * @param $data 数据
     * @return array
     */
    public static function parsePaginate($data)
    {
        $page_info = [
            'current_page' => $data['current_page'],
            'pageSize' => $data['per_page'],
            'last_page' => $data['last_page'],
            'total' => $data['total'],
        ];
        $data = $data['data'];

        return compact('data', 'page_info');
    }

    /**
     * 格式化作品集封面数据
     * @param $data 数据
     * @return array
     */
    public static function parsePhotographerWorkCover($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCover($v, false);
            } else {
                if ($k=='id' && !isset($data['cover'])){
                    $PhotographerWorkImg=PhotographerWorkImg::where(['photographer_work_id'=>$v]);
                    $total = $PhotographerWorkImg->count();
                    $data['cover']='';
                    if($total>0){
                        $skip = mt_rand(0, $total-1);
                        $photographer_work_img=$PhotographerWorkImg->skip($skip)->take(1)->first();
                        $data['cover']=$photographer_work_img->img_url;
                    }
                    break;
                }
            }
        }

        return $data;
    }
}
