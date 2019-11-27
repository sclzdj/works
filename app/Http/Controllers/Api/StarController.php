<?php

namespace App\Http\Controllers\Api;

use App\Model\Admin\SystemArea;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\Star;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class StarController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function __construct()
    {

    }

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function getStars(Request $request)
    {
        $page = $request->input('page', 999);
        $size = $request->input('size' , 15);
        if ($page == 999) {
            $photographer_ids = Star::all()->pluck('photographer_id');
        } else {
            $page = ($page - 1) * $size;
            $photographer_ids = (new Star())->skip($page)->take($size)->pluck('photographer_id');
        }

        $this->data['data'] = Photographer::with(['photographerWorks' => function ($query) {
            $query->where('status', 200);
        }])
            ->whereIn('photographers.id', $photographer_ids)
            ->leftJoin('photographer_ranks', 'photographers.photographer_rank_id', '=', 'photographer_ranks.id')
            ->select([
                'photographers.id', 'photographers.name',
                'photographers.avatar', 'photographer_ranks.name as ranks',
                'photographers.province' , 'photographers.city' , 'photographers.area'
            ])
            ->get();
        foreach ($this->data['data'] as &$datum) {
            $areas = SystemArea::whereIn('id' , [$datum['province'] , $datum['city'] , $datum['area']])->get()->pluck('name');
            $datum['areas'] = $areas;
            $works_ids = $datum['photographerWorks']->pluck('id');
            $datum['cover'] = PhotographerWorkSource::whereIn('photographer_work_id', $works_ids)
                ->where(['status' => 200, 'type' => 'image'])
                ->select(['key' , 'url'])
                ->orderBy('updated_at', 'desc')->limit(3)->get();
            unset($datum['photographerWorks']);
            unset($datum['province']);
            unset($datum['city']);
            unset($datum['area']);
        }
        $this->data['result'] = true;
        return $this->responseParseArray($this->data);
    }


}
