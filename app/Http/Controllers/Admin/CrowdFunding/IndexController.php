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

        // CrowdFunding::initCache();

        $crowdFunding = CrowdFunding::find(1);
        $data = [
            'amount' => CrowdFunding::getKeyValue('amount'),
            'total' => CrowdFunding::getKeyValue('total'),
            'total_price' => CrowdFunding::getKeyValue('total_price'),
            'target' => CrowdFunding::getKeyValue('target'),
            'complete_rate' => CrowdFunding::getKeyValue('complete_rate'),
            'limit_99' => CrowdFunding::getKeyValue('limit_99'),
            'limit_399' => CrowdFunding::getKeyValue('limit_399'),
            'limit_599' => CrowdFunding::getKeyValue('limit_599'),
            'data_99' => CrowdFunding::getKeyValue('data_99'),
            'data_399' => CrowdFunding::getKeyValue('data_399'),
            'data_599' => CrowdFunding::getKeyValue('data_599'),
            'start_date' => date('Y-m-d H:i:s',CrowdFunding::getKeyValue('start_date')),
            'end_date' => date('Y-m-d H:i:s',CrowdFunding::getKeyValue('end_date')),
            'send_date' => date('Y-m-d H:i:s', CrowdFunding::getKeyValue('send_date')),
        ];
        $crowdFunding['total_price'] = CrowdFunding::getKeyValue('total_price');
        return response()->json(compact('crowdFunding', 'data'));
    }

    public function store(Request $request)
    {
        $postData = $request->all();
        $key = $postData['keys'];
        $data = $postData['data'];

        if (empty($data) || empty($key)) {
            return response()->json([
                    'result' => false,
                    'msg' => '参数不能为空'
                ]
            );
        }

        $checkResult = $this->checkRight($postData);
        if (!$checkResult['result']) {
            return response()->json($checkResult);
        }

        switch ($postData['actions']) {
            case "add":
                CrowdFunding::increValue($key, $data);
                CrowdFunding::where('id', 1)
                    ->increment($key, $data);

                switch ($key) {
                    case "data_99":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice + ($data * 99));
                        break;
                    case "data_399":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice + ($data * 399));
                        break;
                    case "data_599":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice + ($data * 599));
                        break;
                }

                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ],
                        'total_price' => CrowdFunding::getKeyValue("total_price")
                    ]
                );
                break;
            case "sub":
                CrowdFunding::decreValue($key, $data);
                CrowdFunding::where('id', 1)
                    ->decrement($key, $data);

                switch ($key) {
                    case "data_99":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice - ($data * 99));
                        break;
                    case "data_399":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice - ($data * 399));
                        break;
                    case "data_599":
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", $totalPrice - ($data * 599));
                        break;
                }

                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ],
                        'total_price' => CrowdFunding::getKeyValue("total_price")
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
                        ],
                        'total_price' => CrowdFunding::getKeyValue("total_price")
                    ]
                );
                break;
            case "set":
                CrowdFunding::ResetValue($key, strtotime($data));
                $result = CrowdFunding::where('id', 1)
                    ->update([
                        $key => strtotime($data)
                    ]);
                return response()->json(
                    [
                        'result' => true,
                        'data' => [
                            $key => $data
                        ],
                        'total_price' => CrowdFunding::getKeyValue("total_price")
                    ]
                );
        }

        return response()->json(
            [
                'result' => false,
            ]
        );
    }

    private function checkRight($postData)
    {
        switch ($postData['keys']) {
            case 'amount':
                break;
            case "limit_399":
                $limit = $postData['data'];
                $data_399 = CrowdFunding::getKeyValue("data_399");
                if ($limit < $data_399) {
                    return [
                        'result' => false,
                        'msg' => '限制值不能小于当前实际值'
                    ];
                }
                break;
            case "limit_99":
                $limit = $postData['data'];
                $data_399 = CrowdFunding::getKeyValue("data_99");
                if ($limit < $data_399) {
                    return [
                        'result' => false,
                        'msg' => '限制值不能小于当前实际值'
                    ];
                }
                break;
            case "limit_599":
                $limit = $postData['data'];
                $data_399 = CrowdFunding::getKeyValue("data_599");
                if ($limit < $data_399) {
                    return [
                        'result' => false,
                        'msg' => '限制值不能小于当前实际值'
                    ];
                }
                break;
            case "data_599":
                $actions = $postData['actions'];
                $data = $postData['data'];
                switch ($actions) {
                    case "add":  // 加不能超过限制值
                        $data_599 = CrowdFunding::getKeyValue("data_599");
                        $limit_599 = CrowdFunding::getKeyValue("limit_599");
                        if ($data_599 + $data > $limit_599) {
                            return [
                                'result' => false,
                                'msg' => '不能超过限制值'
                            ];
                        }
                        break;
                    case "sub": // 减不能减少到0，不能
                        $data_599 = CrowdFunding::getKeyValue("data_599");
                        if ($data_599 - $data < 0)
                            return [
                                'result' => false,
                                'msg' => '不能减少到负数'
                            ];
                        break;
                };
                break;
            case "data_399":
                $actions = $postData['actions'];
                $data = $postData['data'];
                switch ($actions) {
                    case "add":  // 加不能超过限制值
                        $data_399 = CrowdFunding::getKeyValue("data_399");
                        $limit_399 = CrowdFunding::getKeyValue("limit_399");
                        if ($data_399 + $data > $limit_399) {
                            return [
                                'result' => false,
                                'msg' => '不能超过限制值'
                            ];
                        }
                        break;
                    case "sub": // 减不能减少到0，不能
                        $data_399 = CrowdFunding::getKeyValue("data_399");
                        if ($data_399 - $data < 0)
                            return [
                                'result' => false,
                                'msg' => '不能减少到负数'
                            ];
                        break;
                };
                break;
            case "data_99":
                $actions = $postData['actions'];
                $data = $postData['data'];
                switch ($actions) {
                    case "add":  // 加不能超过限制值
                        $data_99 = CrowdFunding::getKeyValue("data_99");
                        $limit_99 = CrowdFunding::getKeyValue("limit_99");
                        if ($data_99 + $data > $limit_99) {
                            return [
                                'result' => false,
                                'msg' => '不能超过限制值'
                            ];
                        }

                        break;
                    case "sub": // 减不能减少到0，不能
                        $data_99 = CrowdFunding::getKeyValue("data_99");
                        if ($data_99 - $data < 0)
                            return [
                                'result' => false,
                                'msg' => '不能减少到负数'
                            ];

                        break;
                };
                break;
        }

        return [
            'result' => true
        ];
    }
}
