<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Http\Requests\Index\UserRequest;
use App\Model\Index\DocPdf;
use App\Model\Index\DocPdfPhotographerWork;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\RandomPhotographer;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
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
        $photographer_works['data'] = ArrServer::toNullStrData(
            $photographer_works['data'],
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
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
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->orderBy('sort', 'asc')->get()->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;

        return $this->response->array($photographer_work);
    }

    /**
     * 保存我的摄影师信息
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerInfo(PhotographerRequest $request)
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
            //验证手机号的唯一性
            $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
                ['mobile' => $request->mobile, 'status' => 200]
            )->first();
            if ($other_photographer) {
                \DB::rollback();//回滚事务

                return $this->response->error('该手机号已被注册成为摄影师了', 500);
            }
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

    /**
     * 修改我的摄影师作品集
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function savePhotographerWorkInfo(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        \DB::beginTransaction();//开启事务
        try {
            $photographer = User::photographer(null, $this->guard);
            $photographer_work = $photographer->photographerWorks()->where(
                ['id' => $request->photographer_work_id, 'status' => 200]
            )->first();
            if (!$photographer_work) {
                return $this->response->error('摄影师作品集不存在', 403);
            }
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
            PhotographerWorkSource::where(['photographer_work_id' => $photographer_work->id])->delete();
            foreach ($request->sources as $k => $v) {
                $photographer_work_source = PhotographerWorkSource::create();
                $photographer_work_source->photographer_work_id = $photographer_work->id;
                $photographer_work_source->url = $v['url'];
                $photographer_work_source->deal_url = $v['url'];//这里需要用七牛技术处理
                $photographer_work_source->type = $v['type'];
                $photographer_work_source->origin = $v['origin'];
                $photographer_work_source->sort = $k + 1;
                $photographer_work_source->save();
            }
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 随机摄影师列表
     * @return mixed|void
     */
    public function randomPhotographers()
    {
        $this->notVisitorIdentityVerify();
        $user = auth($this->guard)->user();
        $random = config('custom.photographer.random');
        \DB::beginTransaction();//开启事务
        try {
            $randomPhotographers = RandomPhotographer::where('user_id', $user->id)->take($random)->get();
            $photographers = [];
            foreach ($randomPhotographers as $randomPhotographer) {
                $photographer = Photographer::select(Photographer::allowFields())->where(
                    ['id' => $randomPhotographer->photographer_id, 'status' => 200]
                )->first();
                if ($photographer) {
                    $photographers[] = $photographer->toArray();
                } else {
                    $randomPhotographer->delete();
                }
            }
            $differ = $random - count($photographers);
            if ($differ > 0) {
                for ($i = 0; $i < $differ; $i++) {
                    $where = ['status' => 200];
                    $total = Photographer::where($where)->whereNotIn('id', ArrServer::ids($photographers))->count();
                    if ($total > 0) {
                        $skip = mt_rand(0, $total - 1);
                        $photographer = Photographer::where($where)->whereNotIn(
                            'id',
                            ArrServer::ids($photographers)
                        )->select(
                            Photographer::allowFields()
                        )->skip($skip)->take(1)->first();
                        if ($photographer) {
                            $randomPhotographer = RandomPhotographer::create();
                            $randomPhotographer->user_id = $user->id;
                            $randomPhotographer->photographer_id = $photographer->id;
                            $randomPhotographer->save();
                            $photographers[] = $photographer->toArray();
                        } else {
                            break;
                        }
                    }

                }
            }
            if ($photographers) {
                $fields = array_map(
                    function ($v) {
                        return 'photographer_work_sources.'.$v;
                    },
                    PhotographerWorkSource::allowFields()
                );
                foreach ($photographers as $k => $photographer) {
                    $photographer_work_sources = PhotographerWorkSource::join(
                        'photographer_works',
                        'photographer_work_sources.photographer_work_id',
                        '=',
                        'photographer_works.id'
                    )->select($fields)
                        ->where(
                            [
                                'photographer_works.status' => 200,
                                'photographer_works.photographer_id' => $photographer['id'],
                                'photographer_work_sources.type' => 'image',
                            ]
                        )
                        ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
                    $photographers[$k]['photographer_work_sources'] = $photographer_work_sources;
                }
                $photographers = SystemServer::parseRegionName($photographers);
            }
            \DB::commit();//提交事务

            return $this->responseParseArray($photographers);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取我的浏览摄影师记录
     * @param UserRequest $request
     * @return mixed
     */
    public function viewRecords(UserRequest $request)
    {
        $user = auth($this->guard)->user();
        $fields = array_map(
            function ($v) {
                return 'photographers.'.$v;
            },
            Photographer::allowFields()
        );
        $view_records = ViewRecord::join(
            'photographers',
            'view_records.photographer_id',
            '=',
            'photographers.id'
        )->select(
            $fields
        )->where(['view_records.user_id' => $user->id, 'photographers.status' => 200])->orderBy(
            'view_records.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $view_records = SystemServer::parsePaginate($view_records->toArray());
        $view_records['data'] = SystemServer::parseRegionName($view_records['data']);
        if ($view_records['data']) {
            $fields = array_map(
                function ($v) {
                    return 'photographer_work_sources.'.$v;
                },
                PhotographerWorkSource::allowFields()
            );
            foreach ($view_records['data'] as $k => $photographer) {
                $photographer_work_sources = PhotographerWorkSource::join(
                    'photographer_works',
                    'photographer_work_sources.photographer_work_id',
                    '=',
                    'photographer_works.id'
                )->select($fields)
                    ->where(
                        [
                            'photographer_works.status' => 200,
                            'photographer_works.photographer_id' => $photographer['id'],
                            'photographer_work_sources.type' => 'image',
                        ]
                    )
                    ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
                $view_records['data'][$k]['photographer_work_sources'] = $photographer_work_sources;
            }
        }

        return $this->response->array($view_records);
    }

    /**
     * 保存pdf
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function saveDocPdf(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        \DB::beginTransaction();//开启事务
        try {
            $doc_pdf = DocPdf::create();
            $doc_pdf->photographer_id = $photographer->id;
            $doc_pdf->name = $request->name;
            $doc_pdf->estimate_completion_time = 60;//需要处理
            foreach ($request->photographer_work_ids as $k => $photographer_work_id) {
                $doc_pdf_photographer_work = DocPdfPhotographerWork::create();
                $doc_pdf_photographer_work->doc_pdf_id = $doc_pdf->id;
                $doc_pdf_photographer_work->photographer_work_id = $photographer_work_id;
                $doc_pdf_photographer_work->sort = $k;
                $doc_pdf_photographer_work->save();
            }
            sleep(100);//需要处理
            $doc_pdf->url = '';
            $doc_pdf->status = 200;
            $doc_pdf->save();
            \DB::commit();//提交事务

            return $this->response->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * pdf列表
     * @param UserRequest $request
     * @return mixed
     */
    public function docPdfs(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $doc_pdfs = DocPdf::select(DocPdf::allowFields())->where('photographer_id', $photographer->id)->whereIn(
            'status',
            [0, 200]
        )->paginate(
            $request->pageSize
        );
        $doc_pdfs = SystemServer::parsePaginate($doc_pdfs->toArray());
        $now_time = time();
        foreach ($doc_pdfs['data'] as $k => $doc_pdf) {
            if ($doc_pdf['status'] == 0) {
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = $doc_pdf['estimate_completion_time'] - ($now_time - strtotime(
                            $doc_pdf['created_at']
                        ));
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = max(
                    0,
                    $doc_pdfs['data'][$k]['residue_estimate_completion_time']
                );
            } else {
                $doc_pdfs['data'][$k]['residue_estimate_completion_time'] = 0;
            }
        }

        return $this->response->array($doc_pdfs);
    }

    /**
     * 获取pdf当前状态
     * @param UserRequest $request
     * @return mixed|void
     */
    public function getDocPdfStatus(UserRequest $request)
    {
        $this->notPhotographerIdentityVerify();
        $photographer = User::photographer(null, $this->guard);
        $doc_pdf = DocPdf::select(DocPdf::allowFields())->where(
            ['photographer_id' => $photographer->id, 'doc_pdf_id' => $request->doc_pdf_id]
        )->first();
        if (!$doc_pdf) {
            return $this->response->error('PDF不存在', 500);
        }

        return $this->responseParseArray(['status' => $doc_pdf->status]);
    }
}
