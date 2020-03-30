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
use App\Model\Index\Templates;
use App\Model\Index\User;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\PhotographerServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use function Qiniu\base64_urlSafeEncode;


/**
 * 用户相关
 * Class SystemController
 * @package App\Http\Controllers\Api
 */
class PhotographerController extends BaseController
{
    /**
     * 用户信息
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function info(PhotographerRequest $request)
    {
        $photographer = $this->_photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);
        $photographer['xacode'] = Photographer::getXacode($photographer['id'], false);

        return $this->responseParseArray($photographer);
    }

    /**
     * 用户项目列表
     * @param PhotographerRequest $request
     */
    public function works(PhotographerRequest $request)
    {
        $photographer = $this->_photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $keywords = $request->keywords;
        if ($request->keywords !== null && $request->keywords !== '') {
            $whereRaw = "(photographer_works.customer_name like ? || photographer_work_customer_industries.name like ? || photographer_work_categories.name like ? || EXISTS (SELECT * from photographer_work_tags WHERE photographer_work_tags.photographer_work_id=photographer_works.id AND photographer_work_tags.name like ?))";
            $whereRaw2 = ["%{$keywords}%", "%{$keywords}%", "%{$keywords}%", "%{$keywords}%"];
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
        );
        if ($request->keywords !== null && $request->keywords !== '') {
            $photographer_works = $photographer_works->whereRaw(
                $whereRaw,
                $whereRaw2
            );
        }
        $photographer_works = $photographer_works->where(['photographer_works.status' => 200])->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
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
            ['sheets_number', 'shooting_duration']
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
     * 用户项目信息
     * @param PhotographerRequest $request
     */
    public function work(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get();
        $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
        $photographer_work_sources = $photographer_work_sources->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank($photographer_work['photographer']);
        $photographer_work['xacode'] = PhotographerWork::getXacode($photographer_work['id'], false);

        return $this->response->array($photographer_work);
    }

    /**
     * 用户项目资源信息
     * @param PhotographerRequest $request
     */
    public function workSource(PhotographerRequest $request)
    {
        $photographer_work_source = PhotographerWorkSource::select(PhotographerWorkSource::allowFields())->where(
            ['status' => 200, 'id' => $request->photographer_work_source_id]
        )->first();
        if (!$photographer_work_source) {
            return $this->response->error('用户项目资源不存在', 500);
        }
        $photographer_work = PhotographerWork::select(PhotographerWork::allowFields())->where(
            ['status' => 200, 'id' => $photographer_work_source->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank($photographer_work['photographer']);
        $photographer_work_source['work'] = $photographer_work;

        return $this->responseParseArray($photographer_work_source);
    }

    /**
     * 获取用户项目的上一个和下一个id
     * @param PhotographerRequest $request
     */
    public function workNext(Request $request)
    {
        $photographer = $this->_photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographerWorks = $photographer->photographerWorks()->where(['photographer_works.status' => 200])->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
            'desc'
        )->get()->pluck('id')->toArray();

        $data = [];
        $data['next'] = 0;
        $data['previous'] = 0;
        foreach ($photographerWorks as $key => $item) {
            if ($item == $request->current_photographerwork_id) {
                $data['next'] = $photographerWorks[$key - 1] ?? 0;
                $data['previous'] = $photographerWorks[$key + 1] ?? 0;
            }
        }

        return $this->response->array($data);
    }

    /**
     * 获取用户作品的上一个和下一个小程序码
     * @param PhotographerRequest $request
     */
    public function xacodeNext(PhotographerRequest $request)
    {
        if ($request->photographer_id > 0) {
            $photographer = $this->_photographer($request->photographer_id);
        } else {
            $photographer = $this->_photographer(null, $this->guards['user']);
        }
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $photographerWorks = $photographer->photographerWorks()->where(['photographer_works.status' => 200])->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
            'desc'
        )->get()->pluck('id')->toArray();
        $next_photographer_work_id = 0;
        $previous_photographer_work_id = 0;
        foreach ($photographerWorks as $key => $item) {
            if ($item == $request->current_photographer_work_id) {
                $next_photographer_work_id = $photographerWorks[$key - 1] ?? 0;
                $previous_photographer_work_id = $photographerWorks[$key + 1] ?? 0;
            }
        }
        if ($next_photographer_work_id > 0) {
            if ($request->is_select_work) {
                $photographer_work = PhotographerWork::find($next_photographer_work_id);
                $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
                    PhotographerWorkSource::allowFields()
                )->where('status', 200)->orderBy('sort', 'asc')->get();
                $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
                $photographer_work_sources = $photographer_work_sources->toArray();
                $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                    PhotographerWorkTag::allowFields()
                )->get()->toArray();
                $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
                $photographer_work = ArrServer::toNullStrData(
                    $photographer_work,
                    ['sheets_number', 'shooting_duration']
                );
                $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
                $photographer_work['sources'] = $photographer_work_sources;
                $photographer_work['tags'] = $photographer_work_tags;
                $photographer_work['photographer'] = ArrServer::inData(
                    $photographer->toArray(),
                    Photographer::allowFields()
                );
                $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
                $photographer_work['photographer'] = SystemServer::parsePhotographerRank(
                    $photographer_work['photographer']
                );
            } else {
                $photographer_work = [];
            }
            $photographer_work['xacode'] = PhotographerWork::getXacode($next_photographer_work_id, false);
            $next = $photographer_work;
        } else {
            $next = [];
        }
        if ($previous_photographer_work_id > 0) {
            if ($request->is_select_work) {
                $photographer_work = PhotographerWork::find($previous_photographer_work_id);
                $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
                    PhotographerWorkSource::allowFields()
                )->where('status', 200)->orderBy('sort', 'asc')->get();
                $photographer_work_sources = SystemServer::getPhotographerWorkSourcesThumb($photographer_work_sources);
                $photographer_work_sources = $photographer_work_sources->toArray();
                $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                    PhotographerWorkTag::allowFields()
                )->get()->toArray();
                $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
                $photographer_work = ArrServer::toNullStrData(
                    $photographer_work,
                    ['sheets_number', 'shooting_duration']
                );
                $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
                $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
                $photographer_work['sources'] = $photographer_work_sources;
                $photographer_work['tags'] = $photographer_work_tags;
                $photographer_work['photographer'] = ArrServer::inData(
                    $photographer->toArray(),
                    Photographer::allowFields()
                );
                $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
                $photographer_work['photographer'] = SystemServer::parsePhotographerRank(
                    $photographer_work['photographer']
                );
            } else {
                $photographer_work = [];
            }
            $photographer_work['xacode'] = PhotographerWork::getXacode($previous_photographer_work_id, false);
            $previous = $photographer_work;
        } else {
            $previous = [];
        }

        return $this->response->array(compact('next', 'previous'));
    }

    /**
     * 用户海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function poster(PhotographerRequest $request)
    {
        $response = Photographer::poster($request->photographer_id);
        if ($response['code'] != 200) {
            return $this->response->error($response['msg'], $response['code']);
        }
        $url = $response['url'];

        return $this->responseParseArray(compact('url'));
    }

    /**
     * 新用户海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function poster2(PhotographerRequest $request)
    {
        $photographer_id = $request->input('photographer_id');
        $response = [];
        $photographer = $this->_photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

            return $response;
        }
        $user = User::where(['photographer_id' => $photographer_id])->first();
        if (!$user) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

            return $response;
        }
        if ($user->identity != 1) {
            $response['code'] = 500;
            $response['msg'] = '用户不是用户';

            return $response;
        }

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $xacode = Photographer::getXacode($photographer_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'roof',
            'desc'
        )->orderBy(
            'created_at',
            'desc'
        )->orderBy(
            'id',
            'desc'
        )->limit(4)->get()->toArray();

        $text = [];
        foreach ($photographer_works as $photographer_work) {
            $text[] = $photographer_work['customer_name'];
        }

        $data = [];
        $data['url1'] = $this->getPersonStyle1(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url2'] = $this->getPersonStyle2(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url3'] = $this->getPersonStyle3(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );

        return $this->responseParseArray($data);
    }


    private function getPersonStyle1($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";
        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/141/dy/334/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/98/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/99/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/99/dy/90/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("Hi!")."/fontsize/2000/fill/".base64_urlSafeEncode(
                "#FFFFFF"
            )."/fontstyle/".base64_urlSafeEncode("Bold")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/NorthWest/dx/101/dy/180/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("我是用户")."/fontsize/2000/fill/".base64_urlSafeEncode(
                "#FFFFFF"
            )."/fontstyle/".base64_urlSafeEncode("Bold")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/NorthWest/dx/101/dy/330/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/NorthWest/dx/101/dy/480/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                'Base'.$photographer_city
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/West/dx/101/dy/-220/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                '擅长'.$photographer_rank.'摄像'
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/West/dx/101/dy/-70/";
        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle2($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {

        $photographerBgImg = "";

        if ($photographer->bg_img) {
            $photographerBgImg = $photographer->bg_img.'?imageMogr2/auto-orient/thumbnail/!1200x1483r/gravity/Center/crop/1200x1483|imageslim';
        } else {
            $photographerBgImg = "https://file.zuopin.cloud/FjeXtrkXjHpqKbEFLvt4ZeadsYZy?imageMogr2/auto-orient/thumbnail/!1200x1483r|imageslim";
        }

        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;

        $handle[] = "|watermark/3/image/".base64_urlSafeEncode($photographerBgImg)."/gravity/North/dx/0/dy/0/";
        $handle[] = "image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";

        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/90/";
        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle3($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";
        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/90/";
        $endKey = count($text);

        $indexPos = 180;
        foreach ($text as $key => $item) {
            $handle[] = "text/".\Qiniu\base64_urlSafeEncode($item).
                "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
                "/fontstyle/".base64_urlSafeEncode("Bold").
                "/font/".base64_urlSafeEncode("Microsoft YaHei").
                "/gravity/NorthWest/dx/100/dy/".($indexPos + ($key * 150))."/";
        }

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("……").
            "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
            "/fontstyle/".base64_urlSafeEncode("Bold").
            "/font/".base64_urlSafeEncode("Microsoft YaHei").
            "/gravity/NorthWest/dx/100/dy/".($indexPos + ($endKey * 160))."/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("都是我拍的").
            "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
            "/fontstyle/".base64_urlSafeEncode("Bold").
            "/font/".base64_urlSafeEncode("Microsoft YaHei").
            "/gravity/West/dx/100/dy/80/";

        $handle[] = "|imageslim";

        return implode($handle);
    }


    /**
     * 用户项目海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function workPoster(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $response = PhotographerWork::poster($request->photographer_work_id);
        if ($response['code'] != 200) {
            return $this->response->error($response['msg'], $response['code']);
        }
        $url = $response['url'];

        return $this->responseParseArray(compact('url'));
    }

    public function workPoster2(Request $request)
    {
        $photographer_work_id = $request->input('photographer_work_id', 0);
        $template_id = $request->input('template_id', 0);
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $photographer_work_id]
        )->first();
        $photographer = $this->_photographer($photographer_work->photographer_id);
        if (!$photographer_work) {
            return $this->response->error('用户项目不存在', 500);
        }
        $photographer = $this->_photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }

        $user = User::where(['photographer_id' => $photographer->id])->first();
        if (!$user) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

            return $response;
        }
        if ($user->identity != 1) {
            $response['code'] = 500;
            $response['msg'] = '用户不是用户';

            return $response;
        }

        $photographer_work_source = $photographer_work->photographerWorkSources()
            ->where(
                ['status' => 200, 'type' => 'image']
            )
            ->orderBy(
                'sort',
                'asc'
            )
            ->first();

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $template = Templates::where('number', $template_id)->first();
        if (empty($template)) {
            return $this->response->error('模板不存在', 500);
        }
        $xacode = PhotographerWork::getXacode($photographer_work_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $workName = $photographer_work->customer_name;
        $name = "{$photographer->name} · 摄影作品";
        $money = "{$photographer_work->project_amount}元 · {$photographer_work->sheets_number}张 · {$photographer_work->shooting_duration}小时";
        $datas = [
            '##money##' => "{$photographer_work->project_amount}",
            '##number##' => "{$photographer_work->sheets_number}",
            '##time##' => "{$photographer_work->shooting_duration}",
            '##customer##' => $workName,
            '##name##' => $photographer->name,
            '##title##' => "{$photographer_rank}摄像师",
        ];

        if (empty($photographer_work_source->deal_key)) {
            return $this->responseParseArray([
                'url' => '',
                'purpose' => $template->purpose,
                'projectName' => $workName,
            ]);
        }

        if ($photographer_work_source->deal_height > 700) {  // 长图
            $width = 1000;
            $height = $photographer_work_source->deal_height;
            $imgs = $domain.'/'.$photographer_work_source->deal_key."?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/1000x700|roundPic/radius/30";
        } else { // 宽图
            $imgs = $domain.'/'.$photographer_work_source->deal_key."?imageMogr2/auto-orient/thumbnail/x600/gravity/Center/crop/!1000x700-0-0|roundPic/radius/30|imageslim";
        }


        $bg = $template->background."?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $writeBg = "https://file.zuopin.cloud/FjRG0YoL-6pTZ8lyjXbkoe4ZFddf";
        $handle = array();
        $handle[] = $bg;
//        $handle[] = "|watermark/3/image/" . $xacodeImgage . "/gravity/SouthEast/dx/180/dy/275/";
//        $handle[] = "/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/South/dx/0/dy/500/";

        $handle[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode($imgs)."/gravity/South/dx/0/dy/500/";
        $handle[] = "/image/".\Qiniu\base64_urlSafeEncode($writeBg)."/gravity/South/dx/0/dy/200/";
        $handle[] = "/image/".$xacodeImgage."/gravity/SouthEast/dx/180/dy/275/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($workName)."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/fontsize/960/fill/".base64_urlSafeEncode("#323232")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/SouthWest/dx/180/dy/470/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($name)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#646464"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/180/dy/340/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($money)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#646464"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/180/dy/270/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看完整作品")."/fontsize/600/fill/".base64_urlSafeEncode(
                "#FFFFFF"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/South/dx/0/dy/86/";

        foreach ($datas as $key => $data) {
            $template->text1 = str_replace($key, $data, $template->text1);
            $template->text2 = str_replace($key, $data, $template->text2);
            $template->text3 = str_replace($key, $data, $template->text3);
            $template->text4 = str_replace($key, $data, $template->text4);
        }

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($template->text1)."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/NorthWest/dx/100/dy/170/";
        if ($template->text2) {
            $handle[] = "text/".\Qiniu\base64_urlSafeEncode($template->text2)."/fontstyle/".base64_urlSafeEncode(
                    "Bold"
                )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/font/".base64_urlSafeEncode(
                    "Microsoft YaHei"
                )."/gravity/NorthWest/dx/100/dy/320/";
        }
        if ($template->text3) {
            $handle[] = "text/".\Qiniu\base64_urlSafeEncode($template->text3)."/fontstyle/".base64_urlSafeEncode(
                    "Bold"
                )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/font/".base64_urlSafeEncode(
                    "Microsoft YaHei"
                )."/gravity/NorthWest/dx/100/dy/470/";
        }

        if ($template->text4) {
            $handle[] = "text/".\Qiniu\base64_urlSafeEncode($template->text4)."/fontstyle/".base64_urlSafeEncode(
                    "Bold"
                )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/font/".base64_urlSafeEncode(
                    "Microsoft YaHei"
                )."/gravity/NorthWest/dx/100/dy/620/";
        }


        $url = implode($handle);
        $purpose = $template->purpose;
        $projectName = $workName;

        return $this->responseParseArray(compact('url', 'purpose' , 'projectName'));
    }

    public function getTemplates()
    {
        $data = Templates::orderBy('number', 'asc')->pluck('number');

        return $this->responseParseArray($data);
    }

    /**
     * 人脉排行榜
     * @param PhotographerRequest $request
     * @return mixed
     */
    public function rankingList(PhotographerRequest $request)
    {
        $limit = $request->limit ?? 50;
        $photographers = PhotographerServer::visitorRankingList($limit);
        $fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            PhotographerWorkSource::allowFields()
        );
        foreach ($photographers as $k => $p) {
            $photographers[$k] = json_decode(json_encode($p), true);
            $work_limit = (int)$request->work_limit;
            if ($work_limit > 0) {
                $photographerWorks = PhotographerWork::select(PhotographerWork::allowFields())->where(
                    [
                        'photographer_id' => $p->id,
                        'status' => 200,
                    ]
                )->orderBy('roof', 'desc')->orderBy(
                    'created_at',
                    'desc'
                )->orderBy('id', 'desc')->take(
                    $work_limit
                )->get();
                $all_tags = [];
                foreach ($photographerWorks as $_k => $photographerWork) {
                    $photographerWorkTags = $photographerWork->photographerWorkTags()->select(
                        PhotographerWorkTag::allowFields()
                    )->get()->toArray();
                    $all_tags[] = $photographerWorkTags;
                }
                $photographerWorks = $photographerWorks->toArray();
                $photographerWorks = ArrServer::toNullStrData(
                    $photographerWorks,
                    ['sheets_number', 'shooting_duration']
                );
                $photographerWorks = ArrServer::inData($photographerWorks, PhotographerWork::allowFields());
                foreach ($photographerWorks as $_k => $v) {
                    $photographerWorks[$_k]['tags'] = $all_tags[$_k];
                }
                $photographerWorks = SystemServer::parsePhotographerWorkCover($photographerWorks);
                $photographerWorks = SystemServer::parsePhotographerWorkCustomerIndustry($photographerWorks);
                $photographerWorks = SystemServer::parsePhotographerWorkCategory($photographerWorks);
                $photographers[$k]['works'] = $photographerWorks;
            }
            $source_limit = (int)$request->source_limit;
            if ($source_limit > 0) {
                $photographerWorkSources = PhotographerWorkSource::select(
                    $fields
                )->join(
                    'photographer_works',
                    'photographer_work_sources.photographer_work_id',
                    '=',
                    'photographer_works.id'
                )->where(
                    [
                        'photographer_works.photographer_id' => $p->id,
                        'photographer_work_sources.status' => 200,
                        'photographer_works.status' => 200,
                        'photographer_work_sources.type' => 'image',
                    ]
                )->orderBy(
                    'photographer_works.roof',
                    'desc'
                )->orderBy(
                    'photographer_works.created_at',
                    'desc'
                )->orderBy(
                    'photographer_works.id',
                    'desc'
                )->orderBy(
                    'photographer_work_sources.sort',
                    'asc'
                )->take($source_limit)->get();
                $photographerWorkSources = SystemServer::getPhotographerWorkSourcesThumb($photographerWorkSources);
                $photographers[$k]['sources'] = $photographerWorkSources->toArray();
            }
        }
        $photographers = SystemServer::parseRegionName($photographers);
        $photographers = SystemServer::parsePhotographerRank($photographers);

        return $this->responseParseArray($photographers);
    }
}
