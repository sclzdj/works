<?php

namespace App\Http\Controllers\Api;

use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
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
        $page = $request->input('page', 0);
        $size = 15;

        if ($page == 0) {
            $photographer_ids = Star::all()->pluck('photographer_id');
            $this->data['data'] = Photographer::with(['photographerWorks' => function ($query) {
                    $query->limit(3);
            }])->whereIn('photographers.id', $photographer_ids)
                ->leftJoin('photographer_ranks', 'photographers.photographer_rank_id', '=', 'photographer_ranks.id')
                ->leftJoin('users', 'photographers.id', '=', 'users.photographer_id')
                ->select([
                    'photographers.id', 'photographers.name',
                    'photographers.avatar', 'photographer_ranks.name as ranks',
                    'users.country' , 'users.province' , 'users.city'
                ])
                ->get();

        }


        return $this->responseParseArray($this->data);
    }


}
