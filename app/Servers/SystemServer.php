<?php

namespace App\Servers;

use App\Model\Admin\SystemArea;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\SmsCode;
use App\Model\Index\VisitorTag;

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
     * @param $random 是否随机取一张图片，否则取第一张
     * @return array
     */
    public static function parsePhotographerWorkCover($data, $random = false)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCover($v, false);
            } else {
                if ($k == 'id' && !isset($data['cover'])) {
                    $where = ['photographer_work_id' => $v, 'type' => 'image'];
                    $total = PhotographerWorkSource::where($where)->count();
                    $data['cover'] = '';
                    if ($total > 0) {
                        if ($random) {
                            $skip = mt_rand(0, $total - 1);
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->skip($skip)->take(1)->first()->toArray();
                            $data['cover'] = $photographer_work_source;
                        } else {
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->orderBy('sort', 'asc')->first()->toArray();
                            $data['cover'] = $photographer_work_source;
                        }
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集客户行业数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerWorkCustomerIndustry($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCustomerIndustry($v);
            } else {
                if ($k == 'photographer_work_customer_industry_id' && !isset($data['photographer_work_customer_industry'])) {
                    $customerIndustry = PhotographerWorkCustomerIndustry::select(
                        PhotographerWorkCustomerIndustry::allowFields()
                    )->where(['id' => $data['photographer_work_customer_industry_id']])->first();
                    $data['photographer_work_customer_industry'] = [];
                    if ($customerIndustry) {
                        $customerIndustry = $customerIndustry->toArray();
                        $data['photographer_work_customer_industry'] = PhotographerWorkCustomerIndustry::elderCustomerIndustries(
                            $data['photographer_work_customer_industry_id']
                        );
                        array_unshift($data['photographer_work_customer_industry'], $customerIndustry);
                        $data['photographer_work_customer_industry'] = array_reverse(
                            $data['photographer_work_customer_industry']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集分类数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerWorkCategory($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCategory($v);
            } else {
                if ($k == 'photographer_work_category_id' && !isset($data['photographer_work_category'])) {
                    $category = PhotographerWorkCategory::select(
                        PhotographerWorkCategory::allowFields()
                    )->where(['id' => $data['photographer_work_category_id']])->first();
                    $data['photographer_work_category'] = [];
                    if ($category) {
                        $category = $category->toArray();
                        $data['photographer_work_category'] = PhotographerWorkCategory::elderCategories(
                            $data['photographer_work_category_id']
                        );
                        array_unshift($data['photographer_work_category'], $category);
                        $data['photographer_work_category'] = array_reverse(
                            $data['photographer_work_category']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集分类数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerRank($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerRank($v);
            } else {
                if ($k == 'photographer_rank_id' && !isset($data['photographer_rank'])) {
                    $rank = PhotographerRank::select(
                        PhotographerRank::allowFields()
                    )->where(['id' => $data['photographer_rank_id']])->first();
                    $data['photographer_rank'] = [];
                    if ($rank) {
                        $rank = $rank->toArray();
                        $data['photographer_rank'] = PhotographerRank::elderRanks(
                            $data['photographer_rank_id']
                        );
                        array_unshift($data['photographer_rank'], $rank);
                        $data['photographer_rank'] = array_reverse(
                            $data['photographer_rank']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化访客标签数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parseVisitorTag($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parseVisitorTag($v);
            } else {
                if ($k == 'visitor_tag_id' && !isset($data['visitor_tag'])) {
                    $tag = VisitorTag::select(
                        VisitorTag::allowFields()
                    )->where(['id' => $data['visitor_tag_id']])->first();
                    $data['visitor_tag'] = [];
                    if ($tag) {
                        $data['visitor_tag'] = $tag->toArray();
                    }
                    break;
                }
            }
        }

        return $data;
    }
}
