<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Requests\Admin\PhotographerRequest;
//use App\Http\Controllers\Admin\BaseController;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\SourceRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\Sources;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

/**
 *
 * Class MyController
 * @package App\Http\Controllers\Api
 */
class SourceController extends BaseController
{
    public function index(){
        $source = Sources::get();
        return $this->responseParseArray($source);
    }


    public function add(SourceRequest $request){
        $name = $request->name;
        $sid = $request->sid;

        $source = new Sources();
        $source->name = $name;
        $source->sid = $sid;
        $source->save();

        return response()->noContent();
    }
}
