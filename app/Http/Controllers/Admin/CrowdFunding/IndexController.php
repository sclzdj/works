<?php

namespace App\Http\Controllers\Admin\CrowdFunding;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\CrowdFunding;
use Illuminate\Http\Request;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/crowdfunding/index');
    }

    public function lists()
    {
        $crowdFunding = CrowdFunding::find(1);

        return response()->json(compact('crowdFunding', 'data'));
    }

    public function store(Request $request)
    {
        $postData = $request->all();
        $key = $postData['keys'];
        $data = $postData['data'];

        switch ($data['actions']) {
            case "add":


                break;
            case "sub":
                break;
            case "rest":


                break;
        }

        return response()->json(['1', '1']);
    }
}
