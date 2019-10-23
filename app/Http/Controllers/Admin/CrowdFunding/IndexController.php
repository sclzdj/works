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
        $data = [
            'amount' => CrowdFunding::getKeyValue('amount'),
            'total' => CrowdFunding::getKeyValue('total'),
            'total_price' => CrowdFunding::getKeyValue('total_price'),
            'target' => CrowdFunding::getKeyValue('target'),
            'complete_rate' => CrowdFunding::getKeyValue('complete_rate'),
            'data_99' => CrowdFunding::getKeyValue('data_99'),
            'data_399' => CrowdFunding::getKeyValue('data_399'),
        ];
        return response()->json(compact('crowdFunding', 'data'));
    }

    public function store(Request $request)
    {
        $postData = $request->all();
        $key = $postData['keys'];
        $data = $postData['data'];

        switch ($postData['actions']) {
            case "add":
                $origin = CrowdFunding::getKeyValue($key);
                CrowdFunding::increValue($key, $data);
                $result = CrowdFunding::where('id', 1)
                    ->increment($key, $data);
                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ]
                    ]
                );
                break;
            case "sub":
                $origin = CrowdFunding::getKeyValue($key);
                CrowdFunding::decreValue($key, $data);
                $result = CrowdFunding::where('id', 1)
                    ->decrement($key, $data);
                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ]
                    ]
                );

                break;
            case "reset":
                CrowdFunding::ResetValue($key, $data);
                $result = CrowdFunding::where('id', 1)
                    ->update([
                        $key => CrowdFunding::getKeyValue($key)
                    ]);
                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ]
                    ]
                );
                break;
        }


    }
}
