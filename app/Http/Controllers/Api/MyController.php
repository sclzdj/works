<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\UserRequest;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkImg;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\SystemServer;

/**
 * 我的相关
 * Class MyController
 * @package App\Http\Controllers\Api
 */
class MyController extends UserGuardController
{
    /**
     * 用户信息保存
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function saveInfo(UserRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $user = auth($this->guard)->user();
            $user->nickname = $request->nickname;
            if ($request->avatar !== null) {
                $user->avatar = $request->avatar;
            }
            if ($request->gender !== null) {
                $user->gender = $request->gender;
            }
            if ($request->country !== null) {
                $user->country = $request->country;
            }
            if ($request->province !== null) {
                $user->province = $request->province;
            }
            if ($request->city !== null) {
                $user->city = $request->city;
            }
            $user->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 刷新token
     *
     * @return mixed
     */
    public function refresh()
    {
        return $this->respondWithToken(auth($this->guard)->refresh());
    }

    /**
     * 退出
     *
     * @return \Dingo\Api\Http\Response
     */
    public function logout()
    {
        auth($this->guard)->logout();

        return $this->response()->noContent();
    }

    /**
     * 我的资料
     *
     * @return \Dingo\Api\Http\Response
     */
    public function info()
    {
        $info = auth($this->guard)->user()->toArray();
        $info = ArrServer::inData($info, User::allowFields());

        return $this->responseParseArray($info);
    }

    /**
     * 用户身份
     * @return mixed
     */
    public function identity()
    {
        $info = auth($this->guard)->user();

        return $this->responseParseArray(['identity' => $info->identity]);
    }

    /**
     * 我的摄影师信息
     *
     * @return \Dingo\Api\Http\Response
     */
    public function photographerInfo()
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);

        return $this->responseParseArray($photographer);
    }

    /**
     * 我的摄影师作品集列表
     * @param UserRequest $request
     */
    public function photographerWorks(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer_works = $photographer->photographerWorks()->where(['status' => 200])->paginate(
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
        $photographer_works['data'] = ArrServer::inData($photographer_works['data'], PhotographerWork::allowFields());
        foreach ($photographer_works['data'] as $k => $v) {
            $photographer_works['data'][$k]['tags'] = $all_tags[$k];
        }
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCover($photographer_works['data']);

        return $this->response->array($photographer_works);
    }

    /**
     * 我的摄影师作品集详情
     * @param UserRequest $request
     */
    public function photographerWork(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer(null, $this->guard);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        if ($photographer_work->photographer_id != $photographer->id) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer_work_imgs = $photographer_work->photographerWorkImgs()->select(
            PhotographerWorkImg::allowFields()
        )->orderBy('sort', 'asc')->get()->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work['imgs'] = $photographer_work_imgs;
        $photographer_work['tags'] = $photographer_work_tags;

        return $this->response->array($photographer_work);
    }

    /**
     * 保存我的摄影师信息
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerInfo(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            //验证短信验证码
            $verify_result = SystemServer::verifySmsCode(
                $request->mobile,
                $request->sms_code,
                'update_my_photographer_info',
                $request->getClientIp()
            );
            if ($verify_result['status'] != 'SUCCESS') {
                \DB::rollback();//回滚事务

                return $this->response->error($verify_result['message'], 500);
            }
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->name = $request->name;
            $photographer->province = $request->province;
            $photographer->city = $request->city;
            $photographer->area = $request->area;
            $photographer->rank = $request->rank;
            $photographer->wechat = $request->wechat;
            $photographer->mobile = $request->mobile;
            $photographer->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的摄影师头像
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerAvatar(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->avatar = (string)$request->avatar;
            $photographer->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 保存我的摄影师背景图片
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerBgImg(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            if (!$photographer || $photographer->status != 200) {
                return $this->response->error('摄影师不存在', 500);
            }
            $photographer->bg_img = (string)$request->bg_img;
            $photographer->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
