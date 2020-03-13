<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\PhotographerWorkRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\Templates;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Illuminate\Http\Request;

class PhotographerWorkController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $filter = [
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'customer_name' => $request['customer_name'] !== null ?
                $request['customer_name'] :
                '',
//            'photographer_work_customer_industry_id' => $request['photographer_work_customer_industry_id'] !== null ?
//                $request['photographer_work_customer_industry_id'] :
//                '',
//            'photographer_work_category_id' => $request['photographer_work_category_id'] !== null ?
//                $request['photographer_work_category_id'] :
//                '',
//            'tag_name' => $request['tag_name'] !== null ?
//                $request['tag_name'] :
//                '',
//            'created_at_start' => $request['created_at_start'] !== null ?
//                $request['created_at_start'] :
//                '',
//            'created_at_end' => $request['created_at_end'] !== null ?
//                $request['created_at_end'] :
//                '',
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'id',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'asc',
        ];
        $where = [];
//        if ($filter['id'] !== '') {
//            $where[] = ['photographer_works.id', 'like', '%'.$filter['id'].'%'];
//        }
        if ($filter['customer_name'] !== '') {
            $where[] = ['photographer_works.customer_name', 'like', '%'.$filter['customer_name'].'%'];
        }
//        if ($filter['tag_name'] !== '') {
//            $where[] = ['photographer_work_tags.name', 'like', '%'.$filter['tag_name'].'%'];
//        }
//        if ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'photographer_works.created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//            $where[] = [
//                'photographer_works.created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] === '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'photographer_works.created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] === ''
//        ) {
//            $where[] = [
//                'photographer_works.created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//        }
        $PhotographerWork = PhotographerWork::select('photographer_works.*')->join(
            'photographers',
            'photographers.id',
            '=',
            'photographer_works.photographer_id'
        );
//        if ($filter['tag_name'] !== '') {
//            $PhotographerWork = $PhotographerWork->join(
//                'photographer_work_tags',
//                'photographer_work_tags.photographer_work_id',
//                '=',
//                'photographer_works.id'
//            );
//        }
        $PhotographerWork = $PhotographerWork->where($where)->where(
            ['photographers.status' => 200, 'photographer_works.status' => 200]
        );
//        if ($filter['photographer_work_customer_industry_id'] !== '') {
//            $photographerWorkCustomerIndustries = PhotographerWorkCustomerIndustry::where(
//                ['pid' => $filter['photographer_work_customer_industry_id']]
//            )->orderBy(
//                'sort',
//                'asc'
//            )->get()->toArray();
//            $photographerWorkCustomerIndustryIds = ArrServer::ids($photographerWorkCustomerIndustries);
//            $photographerWorkCustomerIndustryIds[] = $filter['photographer_work_customer_industry_id'];
//            $PhotographerWork = $PhotographerWork->whereIn(
//                'photographer_works.photographer_work_customer_industry_id',
//                $photographerWorkCustomerIndustryIds
//            );
//        }
//        if ($filter['photographer_work_category_id'] !== '') {
//            $photographerWorkCategories = PhotographerWorkCategory::where(
//                ['pid' => $filter['photographer_work_category_id']]
//            )->orderBy(
//                'sort',
//                'asc'
//            )->get()->toArray();
//            $photographerWorkCategoryIds = ArrServer::ids($photographerWorkCategories);
//            $photographerWorkCategoryIds[] = $filter['photographer_work_category_id'];
//            $PhotographerWork = $PhotographerWork->whereIn(
//                'photographer_works.photographer_work_category_id',
//                $photographerWorkCategoryIds
//            );
//        }
        if ($request->photographer_id > 0) {
            $PhotographerWork = $PhotographerWork->where(
                'photographer_works.photographer_id',
                $request->photographer_id
            );
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first()->toArray(
            );
        } else {
            $photographer = ['id' => 0];
        }
        $photographerWorks = $PhotographerWork->orderBy(
            'photographer_works.'.$orderBy['order_field'],
            $orderBy['order_type']
        )->paginate(
            $pageInfo['pageSize']
        );
        foreach ($photographerWorks as $k => $photographerWork) {
            $photographerWorks[$k]['photographer'] = Photographer::find($photographerWork->photographer_id);
            $photographerWorks[$k]['customer_industry'] = PhotographerWorkCustomerIndustry::find(
                $photographerWork->photographer_work_customer_industry_id
            );
            $photographerWorks[$k]['category'] = PhotographerWorkCategory::find(
                $photographerWork->photographer_work_category_id
            );
            $photographerWorks[$k]['tags'] = PhotographerWorkTag::where(
                'photographer_work_id',
                $photographerWork->id
            )->get();
        }
        $photographerWorkCustomerIndustries = PhotographerWorkCustomerIndustry::select(
            PhotographerWorkCustomerIndustry::allowFields()
        )->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCustomerIndustries as $k => $v) {
            $photographerWorkCustomerIndustries[$k]['children'] = PhotographerWorkCustomerIndustry::select(
                PhotographerWorkCustomerIndustry::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }
        $photographerWorkCategories = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCategories as $k => $v) {
            $photographerWorkCategories[$k]['children'] = PhotographerWorkCategory::select(
                PhotographerWorkCategory::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }
        $templates = Templates::select(['id', 'number', 'purpose'])->orderBy('number', 'asc')->get();

        return view(
            '/admin/works/photographer_work/index',
            compact(
                'photographerWorks',
                'pageInfo',
                'orderBy',
                'filter',
                'photographerWorkCustomerIndustries',
                'photographerWorkCategories',
                'photographer',
                'templates'
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->photographer_id > 0) {
            $photographer = Photographer::where(['id' => $request->photographer_id, 'status' => 200])->first();
            if (!$photographer) {
                return abort('404', '用户不存在');
            }
        } else {
            return abort('404', '用户id必须传递');
        }
        $photographerWorkCustomerIndustries = PhotographerWorkCustomerIndustry::select(
            PhotographerWorkCustomerIndustry::allowFields()
        )->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCustomerIndustries as $k => $v) {
            $photographerWorkCustomerIndustries[$k]['children'] = PhotographerWorkCustomerIndustry::select(
                PhotographerWorkCustomerIndustry::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }
        $photographerWorkCategories = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCategories as $k => $v) {
            $photographerWorkCategories[$k]['children'] = PhotographerWorkCategory::select(
                PhotographerWorkCategory::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }

        return view(
            '/admin/works/photographer_work/create',
            compact(
                'photographerWorkCustomerIndustries',
                'photographerWorkCategories',
                'photographer'
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(PhotographerWorkRequest $photographerWorkRequest)
    {
        $photographer = Photographer::where(
            ['id' => $photographerWorkRequest->photographer_id, 'status' => 200]
        )->first();
        if (!$photographer) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $photographerWorkRequest->all();
            $data = ArrServer::null2strData($data);
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            foreach ($data['sources'] as $k => $source) {
                if (!$source) {
                    unset($data['sources'][$k]);
                } else {
                    $type = 'video';
                    $res = SystemServer::request('GET', $domain.'/'.$source.'?imageInfo');
                    if ($res['code'] == 200) {
                        if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                            $type = 'image';
                        }
                    }
                    $data['sources'][$k] = [];
                    $data['sources'][$k]['key'] = $source;
                    $data['sources'][$k]['type'] = $type;
                }
            }
            if (count($data['sources']) == 0) {
                \DB::rollBack();

                return $this->response('sources格式错误', 500);
            }
            $data['status'] = 200;
            $photographerWork = PhotographerWork::create($data);
            $scene = '1/'.$photographerWork->id;
            $xacode_res = WechatServer::generateXacodes($scene);
            if ($xacode_res['code'] != 200) {
                \DB::rollback();//回滚事务

                return $this->response($xacode_res['msg'], $xacode_res['code']);
            }
            $photographerWork->xacode = $xacode_res['xacode'];
            $photographerWork->xacode_hyaline = $xacode_res['xacode_hyaline'];
            $photographerWork->save();
            PhotographerWorkTag::where(['photographer_work_id' => $photographerWork->id])->delete();
            if ($data['tags']) {
                $data['tags'] = explode(',', $data['tags']);
                foreach ($data['tags'] as $tag) {
                    if ($tag !== '') {
                        PhotographerWorkTag::create(
                            [
                                'photographer_work_id' => $photographerWork->id,
                                'name' => $tag,
                            ]
                        );
                    }
                }
            }
            foreach ($data['sources'] as $k => $v) {
                $photographer_work_source = PhotographerWorkSource::create();
                $photographer_work_source->photographer_work_id = $photographerWork->id;
                $photographer_work_source->key = $v['key'];
                $photographer_work_source->url = $domain.'/'.$v['key'];
                if ($v['type'] != 'image') {
                    $photographer_work_source->deal_key = $v['key'];
                    $photographer_work_source->deal_url = $domain.'/'.$v['key'];
                    $photographer_work_source->rich_key = $v['key'];
                    $photographer_work_source->rich_url = $domain.'/'.$v['key'];
                }
                $photographer_work_source->type = $v['type'];
                $photographer_work_source->origin = 'system';
                $photographer_work_source->sort = $k + 1;
                $photographer_work_source->status = 200;
                $photographer_work_source->save();
                if ($photographer_work_source->type == 'image') {
                    $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                    if ($res['code'] == 200) {
                        if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                            $photographer_work_source->size = $res['data']['size'];
                            $photographer_work_source->width = $res['data']['width'];
                            $photographer_work_source->height = $res['data']['height'];
//                                $photographer_work_source->deal_size = $res['data']['size'];
//                                $photographer_work_source->deal_width = $res['data']['width'];
//                                $photographer_work_source->deal_height = $res['data']['height'];
//                                $photographer_work_source->rich_size = $res['data']['size'];
//                                $photographer_work_source->rich_width = $res['data']['width'];
//                                $photographer_work_source->rich_height = $res['data']['height'];
                            $photographer_work_source->save();
                            $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                            $bucket = 'zuopin';
                            $qrst = SystemServer::qiniuPfop(
                                $bucket,
                                $photographer_work_source->key,
                                $fops,
                                null,
                                config(
                                    'app.url'
                                ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographer_work_source->id,
                                true
                            );
                            if ($qrst['err']) {
                                ErrLogServer::QiniuNotifyFop(
                                    '处理图片持久请求',
                                    '七牛持久化接口返回错误信息',
                                    $photographerWorkRequest->all(),
                                    $photographer_work_source,
                                    $qrst['err']
                                );
                            }
                            PhotographerWorkSource::editRunGenerateWatermark($photographer_work_source->id, '后台添加项目');
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                '原始图片信息请求',
                                '七牛图片信息接口返回错误信息',
                                $photographerWorkRequest->all(),
                                $photographer_work_source,
                                $res['data']
                            );
                        }
                    } else {
                        ErrLogServer::QiniuNotifyFop(
                            '原始图片信息请求',
                            '请求七牛图片信息接口报错：'.$res['msg'],
                            $photographerWorkRequest->all(),
                            $photographer_work_source,
                            $res
                        );
                    }
                } elseif ($photographer_work_source->type == 'video') {
                    $res = SystemServer::request('GET', $photographer_work_source->url.'?avinfo');
                    if ($res['code'] == 200) {
                        if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                            $photographer_work_source->size = $res['data']['format']['size'];
                            $photographer_work_source->deal_size = $res['data']['format']['size'];
                            $photographer_work_source->rich_size = $res['data']['format']['size'];
                            $photographer_work_source->save();
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                '原始视频信息请求',
                                '七牛视频信息接口返回错误信息',
                                $photographerWorkRequest->all(),
                                $photographer_work_source,
                                $res['data']
                            );
                        }
                    } else {
                        ErrLogServer::QiniuNotifyFop(
                            '原始视频信息请求',
                            '请求七牛视频信息接口报错：'.$res['msg'],
                            $photographerWorkRequest->all(),
                            $photographer_work_source,
                            $res
                        );
                    }
                }
            }
            $response = [
                'url' => action(
                    'Admin\Works\PhotographerWorkController@index',
                    ['photographer_id' => $photographer->id]
                ),
            ];
            \DB::commit();//提交事务

            return $this->response('添加成功', 200, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if ($id > 0) {
            $photographerWork = PhotographerWork::where(
                ['id' => $id, 'status' => 200]
            )->first();
            if (!$photographerWork) {
                return abort('404', '项目不存在');
            }
        } else {
            return abort('404', '项目id必须传递');
        }
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id, 'status' => 200])->first();
        if (!$photographer) {
            return abort('404', '用户不存在');
        }
        $photographerWorkCustomerIndustries = PhotographerWorkCustomerIndustry::select(
            PhotographerWorkCustomerIndustry::allowFields()
        )->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCustomerIndustries as $k => $v) {
            $photographerWorkCustomerIndustries[$k]['children'] = PhotographerWorkCustomerIndustry::select(
                PhotographerWorkCustomerIndustry::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }
        $photographerWorkCategories = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerWorkCategories as $k => $v) {
            $photographerWorkCategories[$k]['children'] = PhotographerWorkCategory::select(
                PhotographerWorkCategory::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }
        $photographerWorkTags = implode(
            ',',
            ArrServer::ids(
                PhotographerWorkTag::where(
                    'photographer_work_id',
                    $photographerWork->id
                )->get()->toArray(),
                'name'
            )
        );
        $sources = $photographerWork->photographerWorkSources()->where(['status' => 200])->orderBy(
            'sort',
            'asc'
        )->get()->toArray();
        $photographerWorkSources = [];
        $i = 0;
        foreach ($sources as $k => $source) {
            $tmp = $source['key'];
            $photographerWorkSources[] = $tmp;
            $i++;
        }
        if ($i < 8) {
            for ($i; $i < 9; $i++) {
                $photographerWorkSources[$i] = '';
            }
        }

        return view(
            '/admin/works/photographer_work/edit',
            compact(
                'photographerWorkCustomerIndustries',
                'photographerWorkCategories',
                'photographer',
                'photographerWork',
                'photographerWorkSources',
                'photographerWorkTags'
            )
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(PhotographerWorkRequest $photographerWorkRequest, $id)
    {
        $photographerWork = PhotographerWork::where(
            ['id' => $id, 'status' => 200]
        )->first();
        if (!$photographerWork) {
            return $this->response('参数无效', 403);
        }
        $old_work_params = [
            'customer_name' => $photographerWork->customer_name,
            'source_count' => $photographerWork->photographerWorkSources()->where(['status' => 200])->count(),
        ];
        $photographer = Photographer::where(
            ['id' => $photographerWork->photographer_id, 'status' => 200]
        )->first();
        if (!$photographer) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $photographerWorkRequest->all();
            $data = ArrServer::null2strData($data);
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            foreach ($data['sources'] as $k => $source) {
                if (!$source) {
                    unset($data['sources'][$k]);
                } else {
                    $type = 'video';
                    $res = SystemServer::request('GET', $domain.'/'.$source.'?imageInfo');
                    if ($res['code'] == 200) {
                        if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                            $type = 'image';
                        }
                    }
                    $data['sources'][$k] = [];
                    $data['sources'][$k]['key'] = $source;
                    $data['sources'][$k]['type'] = $type;
                }
            }
            if (count($data['sources']) == 0) {
                \DB::rollBack();

                return $this->response('sources格式错误', 500);
            }
            $update = $data;
            unset($update['tags']);
            unset($update['sources']);
            if (isset($update['s'])) {
                unset($update['s']);
            }
            PhotographerWork::where(['id' => $photographerWork->id, 'status' => 200])->update($update);
            $photographerWork = PhotographerWork::where(
                ['id' => $id, 'status' => 200]
            )->first();
            PhotographerWorkTag::where(['photographer_work_id' => $photographerWork->id])->delete();
            if ($data['tags']) {
                $data['tags'] = explode(',', $data['tags']);
                foreach ($data['tags'] as $tag) {
                    if ($tag !== '') {
                        PhotographerWorkTag::create(
                            [
                                'photographer_work_id' => $photographerWork->id,
                                'name' => $tag,
                            ]
                        );
                    }
                }
            }
            $photographerWork->photographerWorkSources()->where(['status' => 200])->update(['status' => 300]);
            foreach ($data['sources'] as $k => $v) {
                $photographer_work_source = PhotographerWorkSource::where(
                    ['photographer_work_id' => $photographerWork->id, 'status' => 300, 'key' => $v['key']]
                )->first();
                if ($photographer_work_source) {
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                }
                else {
                    $photographer_work_source = PhotographerWorkSource::create();
                    $photographer_work_source->photographer_work_id = $photographerWork->id;
                    $photographer_work_source->key = $v['key'];
                    $photographer_work_source->url = $domain.'/'.$v['key'];
                    if ($v['type'] != 'image') {
                        $photographer_work_source->deal_key = $v['key'];
                        $photographer_work_source->deal_url = $domain.'/'.$v['key'];
                        $photographer_work_source->rich_key = $v['key'];
                        $photographer_work_source->rich_url = $domain.'/'.$v['key'];
                    }
                    $photographer_work_source->type = $v['type'];
                    $photographer_work_source->origin = 'system';
                    $photographer_work_source->sort = $k + 1;
                    $photographer_work_source->status = 200;
                    $photographer_work_source->save();
                    if ($photographer_work_source->type == 'image') {
                        $photographer_work_source->is_new_source = 1;
                        $photographer_work_source->save();
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?imageInfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['size'];
                                $photographer_work_source->width = $res['data']['width'];
                                $photographer_work_source->height = $res['data']['height'];
//                                $photographer_work_source->deal_size = $res['data']['size'];
//                                $photographer_work_source->deal_width = $res['data']['width'];
//                                $photographer_work_source->deal_height = $res['data']['height'];
//                                $photographer_work_source->rich_size = $res['data']['size'];
//                                $photographer_work_source->rich_width = $res['data']['width'];
//                                $photographer_work_source->rich_height = $res['data']['height'];
                                $photographer_work_source->save();
                                $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                                $bucket = 'zuopin';
                                $qrst = SystemServer::qiniuPfop(
                                    $bucket,
                                    $photographer_work_source->key,
                                    $fops,
                                    null,
                                    config(
                                        'app.url'
                                    ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographer_work_source->id,
                                    true
                                );
                                if ($qrst['err']) {
                                    ErrLogServer::QiniuNotifyFop(
                                        '处理图片持久请求',
                                        '七牛持久化接口返回错误信息',
                                        $photographerWorkRequest->all(),
                                        $photographer_work_source,
                                        $qrst['err']
                                    );
                                }
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    '原始图片信息请求',
                                    '七牛图片信息接口返回错误信息',
                                    $photographerWorkRequest->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                '原始图片信息请求',
                                '请求七牛图片信息接口报错：'.$res['msg'],
                                $photographerWorkRequest->all(),
                                $photographer_work_source,
                                $res
                            );
                        }
                    }
                    elseif ($photographer_work_source->type == 'video') {
                        $res = SystemServer::request('GET', $photographer_work_source->url.'?avinfo');
                        if ($res['code'] == 200) {
                            if (!isset($res['data']['error']) || (isset($res['data']['code']) && $res['data']['code'] == 200)) {
                                $photographer_work_source->size = $res['data']['format']['size'];
                                $photographer_work_source->deal_size = $res['data']['format']['size'];
                                $photographer_work_source->rich_size = $res['data']['format']['size'];
                                $photographer_work_source->save();
                            } else {
                                ErrLogServer::QiniuNotifyFop(
                                    '原始视频信息请求',
                                    '七牛视频信息接口返回错误信息',
                                    $photographerWorkRequest->all(),
                                    $photographer_work_source,
                                    $res['data']
                                );
                            }
                        } else {
                            ErrLogServer::QiniuNotifyFop(
                                '原始视频信息请求',
                                '请求七牛视频信息接口报错：'.$res['msg'],
                                $photographerWorkRequest->all(),
                                $photographer_work_source,
                                $res
                            );
                        }
                    }
                }
            }
            $photographerWork->photographerWorkSources()->where(['status' => 300])->update(['status' => 400]);
            $new_work_params = [
                'customer_name' => $photographerWork->customer_name,
                'source_count' => $photographerWork->photographerWorkSources()->where(['status' => 200])->count(),
            ];
            $photographerWorkSources=$photographerWork->photographerWorkSources()->where(['status' => 200,'type'=>'image'])->orderBy('sort','asc')->get();
            $editIsRunGenerateWatermark=PhotographerWorkSource::editIsRunGenerateWatermark($new_work_params,$old_work_params);
            foreach ($photographerWorkSources as $photographerWorkSource){
                if($editIsRunGenerateWatermark || $photographerWorkSource->is_new_source){
                    PhotographerWorkSource::editRunGenerateWatermark($photographerWorkSource->id,'后台修改项目');
                    if($photographerWorkSource->is_new_source){
                        $photographerWorkSource->is_new_source=0;
                        $photographerWorkSource->save();
                    }
                }
            }
            $response = [
                'url' => action(
                    'Admin\Works\PhotographerWorkController@index',
                    ['photographer_id' => $photographer->id]
                ),
            ];
            \DB::commit();//提交事务

            return $this->response('修改成功', 200, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                $photographer_work = PhotographerWork::where(
                    ['status' => 200, 'id' => $id]
                )->first();
                if (!$photographer_work) {
                    \DB::rollback();//回滚事务

                    return $this->response('用户项目不存在', 500);
                }
                $count = PhotographerWork::where('id', '!=', $id)->where(
                    ['photographer_id' => $photographer_work->photographer_id, 'status' => 200]
                )->count();
                if ($count == 0) {
                    \DB::rollback();//回滚事务

                    return $this->response('每个用户至少保留一个项目', 500);
                }
                $photographer_work->status = 400;
                $photographer_work->save();

                \DB::commit();//提交事务

                return $this->response('删除成功', 200);
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                foreach ($ids as $id) {
                    $photographer_work = PhotographerWork::where(
                        ['status' => 200, 'id' => $id]
                    )->first();
                    if (!$photographer_work) {
                        continue;
                    }
                    $count = PhotographerWork::whereNotIn('id', $ids)->where(
                        ['photographer_id' => $photographer_work->photographer_id, 'status' => 200]
                    )->count();
                    if ($count == 0) {
                        \DB::rollback();//回滚事务

                        return $this->response('每个用户至少保留一个项目', 500);
                    }
                    $photographer_work->status = 400;
                    $photographer_work->save();
                }
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * 项目海报
     * @param Request $request
     */
    public function poster(Request $request)
    {
        $poster = PhotographerWork::poster2($request->id, $request->template_id);

        return $this->response('请求成功', 200, $poster);
    }
}
