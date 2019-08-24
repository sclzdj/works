<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkImg;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;

/**
 * 草稿相关
 * Class DraftController
 * @package App\Http\Controllers\Api
 */
class DraftController extends UserGuardController
{
    /**
     * 查出摄影师注册作品集图片
     * @return mixed|void
     */
    public function registerPhotographerWorkImg()
    {
        $this->notVisitorIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        $photographer_work_imgs = $photographer_work->photographerWorkImgs()->select(
            PhotographerWorkImg::allowFields()
        )->orderBy('sort', 'asc')->get()->toArray();

        return $this->responseParseArray($photographer_work_imgs);
    }

    /**
     * 保存摄影师注册作品集图片
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkImgStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
            PhotographerWorkImg::where(['photographer_work_id' => $photographer_work->id])->delete();
            foreach ($request->img_urls as $k => $v) {
                $Photographer_work_img = PhotographerWorkImg::create();
                $Photographer_work_img->photographer_work_id = $photographer_work->id;
                $Photographer_work_img->img_url = $v;
                $Photographer_work_img->sort = $k + 1;
                $Photographer_work_img->save();
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出摄影师注册作品集信息
     * @return mixed|void
     */
    public function registerPhotographerWork()
    {
        $this->notVisitorIdentityVerify();
        $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(['status' => 0])->first(
        );
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work['tags'] = $photographer_work_tags;

        return $this->responseParseArray($photographer_work);
    }

    /**
     * 保存摄影师注册作品集信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerWorkStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
            $photographer_work->customer_name = $request->customer_name;
            $photographer_work->customer_industry = $request->customer_industry;
            $photographer_work->project_amount = $request->project_amount;
            $photographer_work->hide_project_amount = $request->hide_project_amount;
            $photographer_work->sheets_number = $request->sheets_number;
            $photographer_work->hide_sheets_number = $request->hide_sheets_number;
            $photographer_work->shooting_duration = $request->shooting_duration;
            $photographer_work->hide_shooting_duration = $request->hide_shooting_duration;
            $photographer_work->category = $request->category;
            $photographer_work->save();
            PhotographerWorkTag::where(['photographer_work_id' => $photographer_work->id])->delete();
            if ($request->tags) {
                foreach ($request->tags as $v) {
                    $photographer_work_tag = PhotographerWorkTag::create();
                    $photographer_work_tag->photographer_work_id = $photographer_work->id;
                    $photographer_work_tag->name = $v;
                    $photographer_work_tag->save();
                }
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 查出摄影师注册信息
     * @return mixed|void
     */
    public function registerPhotographer()
    {
        $this->notVisitorIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());

        return $this->responseParseArray($photographer);
    }

    /**
     * 保存摄影师注册信息
     * @param PhotographerRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function registerPhotographerStore(PhotographerRequest $request)
    {
        $this->notVisitorIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            //验证短信验证码
            $verify_result = SystemServer::verifySmsCode(
                $request->mobile,
                $request->sms_code,
                'photographer_register',
                $request->getClientIp()
            );
            if ($verify_result['status'] != 'SUCCESS') {
                \DB::rollback();//回滚事务

                return $this->response->error($verify_result['message'], 500);
            }
            $photographer = User::photographer(null, $this->guard);
            $photographer->name = $request->name;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->rank = $request->rank;
            $photographer->wechat = $request->wechat;
            $photographer->mobile = $request->mobile;
            $photographer->status = 200;
            $photographer->save();
            $photographer_work = $photographer->photographerWorks()->where(['status' => 0])->first();
            $photographer_work->status = 200;
            $photographer_work->save();
            $user = auth($this->guard)->user();
            $user->identity = 1;
            $user->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
