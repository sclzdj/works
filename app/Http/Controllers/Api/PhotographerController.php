<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;


use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Admin\SystemArea;
use App\Model\Index\OperateRecord;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\SystemServer;

/**
 * 摄影师相关
 * Class SystemController
 * @package App\Http\Controllers\Api
 */
class PhotographerController extends BaseController
{
    /**
     * 摄影师信息
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function info(PhotographerRequest $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);

        return $this->responseParseArray($photographer);
    }

    /**
     * 摄影师作品集列表
     * @param PhotographerRequest $request
     */
    public function works(PhotographerRequest $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $keywords = $request->keywords;
        $whereRaw = '1';
        if (!empty($keywords)) {
            $whereRaw = "(photographer_works.customer_name like '%{$keywords}%' || photographer_work_customer_industries.name like '%{$keywords}%' || photographer_work_categories.name like '%{$keywords}%' || photographer_work_tags.name like '%{$keywords}%')";
        }
        $photographer_works = $photographer->photographerWorks()->select('photographer_works.*')->join(
            'photographer_work_customer_industries',
            'photographer_works.photographer_work_customer_industry_id',
            '=',
            'photographer_work_customer_industries.id'
        )->join(
            'photographer_work_categories',
            'photographer_works.photographer_work_category_id',
            '=',
            'photographer_work_categories.id'
        )->join(
            'photographer_work_tags',
            'photographer_work_tags.photographer_work_id',
            '=',
            'photographer_works.id'
        )->where(['photographer_works.status' => 200])->whereRaw($whereRaw)->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $all_tags = [];
        foreach ($photographer_works as $k => $photographer_work) {
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $all_tags[] = $photographer_work_tags;
        }
        $photographer_works = SystemServer::parsePaginate($photographer_works->toArray());
        $photographer_works = ArrServer::toNullStrData(
            $photographer_works,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_works['data'] = ArrServer::inData($photographer_works['data'], PhotographerWork::allowFields());
        foreach ($photographer_works['data'] as $k => $v) {
            $photographer_works['data'][$k]['tags'] = $all_tags[$k];
        }
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCover($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCategory($photographer_works['data']);

        return $this->response->array($photographer_works);
    }

    /**
     * 摄影师作品集信息
     * @param PhotographerRequest $request
     */
    public function work(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get()->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank($photographer_work['photographer']);

        return $this->response->array($photographer_work);
    }

    /**
     * 摄影师海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function poster(PhotographerRequest $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $user = User::where(['photographer_id' => $request->photographer_id])->first();
        if (!$user) {
            return $this->response->error('用户不存在', 500);
        }
        if ($user->identity != 1) {
            return $this->response->error('用户不是摄影师', 500);
        }
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $url = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg');
        $deals = [];
        $deals[] = 'imageMogr2/crop/1200x2133';
        if ($photographer->bg_img) {
            $photographer->bg_img = $photographer->bg_img.'?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x600-0-0|imageslim';
        } else {
            $photographer->bg_img = config('app.url').'/'.'images/poster_bg.jpg';
        }
        if ($photographer->avatar) {
            $photographer->avatar = $photographer->avatar.'?imageMogr2/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        } else {
            $photographer->avatar = $domain.'/'.config('custom.qiniu.avatar').'?imageMogr2/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        }
        if ($user->xacode) {
            $user->xacode = $user->xacode.'|imageMogr2/thumbnail/250x250!';
        } else {
            $user->xacode = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg').'?imageMogr2/crop/250x250';
        }
        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'created_at',
            'desc'
        )->limit(4)->get()->toArray();
        if ($photographer_works_count > 4) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'].'·'.$photographer_works[3]['customer_name'];
            $text3 = '……';
        } elseif ($photographer_works_count == 4) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'].'·'.$photographer_works[3]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 3) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 2) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = '';
            $text3 = '';
        } elseif ($photographer_works_count == 1) {
            $text1 = $photographer_works[0]['customer_name'];
            $text2 = '';
            $text3 = '';
        } else {
            $text1 = '';
            $text2 = '';
            $text3 = '';
        }
        $watermark = 'watermark/3/image/'.\Qiniu\base64_urlSafeEncode($photographer->bg_img).'/gravity/North/dx/0/dy/0';
        $watermark .= '/image/'.\Qiniu\base64_urlSafeEncode($photographer->avatar).'/gravity/North/dx/0/dy/450';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                'Hi！我是摄影师'.$photographer->name
            ).'/fontsize/1500/fill/'.\Qiniu\base64_urlSafeEncode('#313131').'/gravity/North/dx/0/dy/900';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '坐标'.$photographer_city.'·'.'擅长'.$photographer_rank.'摄影'
            ).'/fontsize/1000/fill/'.\Qiniu\base64_urlSafeEncode('#696969').'/gravity/North/dx/0/dy/1060';
        if ($text1) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text1
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1250';
        }
        if ($text2) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text2
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1330';
        }
        if ($text3) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text3
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1410';
        }
        $watermark .= '/image/'.\Qiniu\base64_urlSafeEncode($user->xacode).'/gravity/North/dx/0/dy/1630';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '微信扫一扫 看我的作品'
            ).'/fontsize/700/fill/'.\Qiniu\base64_urlSafeEncode('#4E4E4E').'/gravity/North/dx/0/dy/1950';
        $deals[] = $watermark;
        $url .= '?'.implode('|', $deals);
        //记录
        \DB::beginTransaction();//开启事务
        try {
            $user = auth('users')->user();
            if ($user) {
                $operate_record = OperateRecord::create();
                $operate_record->user_id = $user->id;
                $operate_record->page_name = 'photographer_home';
                $operate_record->photographer_id = $photographer->id;
                $operate_record->share_type = 'poster_share';
                $operate_record->operate_type = 'share';
                $operate_record->save();
                if ($user->id != $photographer->id) {//如果不是自己访问，记录访客信息
                    $visitor = Visitor::where(
                        ['photographer_id' => $photographer->id, 'user_id' => $user->id]
                    )->first();
                    if (!$visitor) {
                        $visitor = Visitor::create();
                        $visitor->photographer_id = $photographer->id;
                        $visitor->user_id = $user->id;
                    }
                    $visitor->unread_count++;
                    $visitor->save();
                }
            }
            \DB::commit();//提交事务

            return $this->responseParseArray(compact('url'));
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 摄影师作品集海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function workPoster(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $user = User::where(['photographer_id' => $photographer->id])->first();
        if (!$user) {
            return $this->response->error('用户不存在', 500);
        }
        if ($user->identity != 1) {
            return $this->response->error('用户不是摄影师', 500);
        }
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $url = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg');
        $deals = [];
        $deals[] = 'imageMogr2/crop/1200x2133';
        $photographer_work_source = $photographer_work->photographerWorkSources()->where(
            ['status' => 200, 'type' => 'image']
        )->orderBy(
            'sort',
            'asc'
        )->first();
        if ($photographer_work_source) {
            if ($photographer_work_source->deal_url) {
                $bg_img = $photographer_work_source->deal_url.'?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } elseif ($photographer_work_source->url) {
                $bg_img = $photographer_work_source->url.'?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } elseif ($photographer->bg_img) {
                $bg_img = $photographer->bg_img.'?imageslim|imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0';
            } else {
                $bg_img = config('app.url').'/'.'images/poster_bg.jpg';
            }
        } else {
            if ($photographer->bg_img) {
                $bg_img = $photographer->bg_img.'?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } else {
                $bg_img = config('app.url').'/'.'images/poster_bg.jpg';
            }
        }
        $xacode = User::createXacode($request->photographer_work_id, 'photographer_work');
        if ($xacode) {
            $xacode = $xacode.'|imageMogr2/thumbnail/250x250!';
        } else {
            $xacode = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg').'?imageMogr2/crop/250x250';
        }
        $watermark = 'watermark/3/image/'.\Qiniu\base64_urlSafeEncode($bg_img).'/gravity/North/dx/0/dy/0';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '我是摄影师'.$photographer->name
            ).'/fontsize/1500/fill/'.\Qiniu\base64_urlSafeEncode('#313131').'/gravity/North/dx/0/dy/950';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '我为'.$photographer_work->customer_name
            ).'/fontsize/1500/fill/'.\Qiniu\base64_urlSafeEncode('#313131').'/gravity/North/dx/0/dy/1100';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '拍了一组作品'
            ).'/fontsize/1500/fill/'.\Qiniu\base64_urlSafeEncode('#313131').'/gravity/North/dx/0/dy/1250';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                $photographer_work->project_amount.'元·'.$photographer_work->sheets_number.'张·'.$photographer_work->shooting_duration.'小时'
            ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1420';
        $watermark .= '/image/'.\Qiniu\base64_urlSafeEncode($xacode).'/gravity/North/dx/0/dy/1630';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '微信扫一扫 看完整作品'
            ).'/fontsize/700/fill/'.\Qiniu\base64_urlSafeEncode('#4E4E4E').'/gravity/North/dx/0/dy/1950';
        $deals[] = $watermark;
        $url .= '?'.implode('|', $deals);
        //记录
        \DB::beginTransaction();//开启事务
        try {
            $user = auth('users')->user();
            if ($user) {
                $operate_record = OperateRecord::create();
                $operate_record->user_id = $user->id;
                $operate_record->page_name = 'photographer_work';
                $operate_record->photographer_id = $photographer->id;
                $operate_record->photographer_work_id = $photographer_work->id;
                $operate_record->share_type = 'poster_share';
                $operate_record->operate_type = 'share';
                $operate_record->save();
                if ($user->id != $photographer->id) {//如果不是自己访问，记录访客信息
                    $visitor = Visitor::where(
                        ['photographer_id' => $photographer->id, 'user_id' => $user->id]
                    )->first();
                    if (!$visitor) {
                        $visitor = Visitor::create();
                        $visitor->photographer_id = $photographer->id;
                        $visitor->user_id = $user->id;
                    }
                    $visitor->unread_count++;
                    $visitor->save();
                }
            }
            \DB::commit();//提交事务

            return $this->responseParseArray(compact('url'));
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
