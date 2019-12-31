<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\Star;
use App\Model\Index\Templates;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;
use function Qiniu\base64_urlSafeEncode;

class TemplatesController extends BaseController
{

    public function index()
    {
        return view('admin/templates/index');
    }

    public function lists(Request $request)
    {
        $id = $request->input('id', 0);
        if ($id) {
            $template = Templates::where(compact('id'))->first();
            return response()->json(compact('template'));
        }
        $page = $request->input('page', 1);
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
        $data = Templates::where($where)->skip($page)->take($size)->orderBy('created_at', 'desc')->get();
        $count = Templates::where($where)->count();

        foreach ($data as &$datum) {
            $datum['type'] = $datum['type'] == 1 ? '用户创建' : '后台创建';
            $datum['status'] = $statusArr[$datum['status']] ?? '未知';
        }

        return response()->json(compact('data', 'count'));
    }

    public function store(Request $request)
    {
        $form = $request->input('form');
        if (isset($form['id']) && $form['id']) {
            $validateRequest = Validator::make(
                $form, [
                'purpose' => 'required|max:50',
                'text1' => 'required|max:50',
                'background' => 'required',
            ], [
                'purpose' => [
                    'required' => '用途必须传',
                ],
                'text1' => [
                    'required' => '文案必须传',
                ],
                'background' => [
                    'required' => '背景图必须传',
                ],
            ]);
            if ($validateRequest->fails()) {
                $msg = $validateRequest->errors()->all();
                return [
                    'result' => false,
                    'msg' => array_shift($msg)
                ];
            }
            $template = Templates::where('id', $form['id'])->first();
            if ($template->number != $form['number'] && Templates::where('number', $form['number'])->first()) {
                return [
                    'result' => false,
                    'msg' => 'number 存在'
                ];
            }

            $result = Templates::where('id', $form['id'])->update([
                'number' => $form['number'],
                'purpose' => $form['purpose'],
                'text1' => $form['text1'],
                'text2' => $form['text2'],
                'text3' => $form['text3'],
                'text4' => $form['text4'],
                'background' => $form['background']
            ]);
            if ($result) {
                return [
                    'result' => true,
                    'msg' => '保存成功'
                ];
            } else {
                return [
                    'result' => false,
                    'msg' => '保存失败'
                ];
            }
        }

        $validateRequest = Validator::make(
            $form, [
            'number' => 'required|Numeric|unique:templates',
            'purpose' => 'required|max:50',
            'text1' => 'required|max:50',
            'background' => 'required',
        ], [
            'number' => [
                'required' => '序号必须传',
                'unique' => '序号重复'
            ],
            'purpose' => [
                'required' => '用途必须传',
            ],
            'text1' => [
                'required' => '文案必须传',
            ],
            'background' => [
                'required' => '背景图必须传',
            ],
        ]);
        if ($validateRequest->fails()) {
            $msg = $validateRequest->errors()->all();
            return [
                'result' => false,
                'msg' => array_shift($msg)
            ];
        }
        $form['created_at'] = date('Y-m-d H:i:s');
        $result = Templates::insert($form);
        if ($result) {
            return [
                'result' => true,
                'msg' => '添加成功'
            ];
        } else {
            return [
                'result' => false,
                'msg' => '添加失败'
            ];
        }
    }

    public function edit($id)
    {
        $data['id'] = $id;
        return view('admin/templates/edit', $data);
    }

    public function show($id)
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $photographer_id = 16;

        $template = Templates::where('id', $id)->first();
        if (empty($template)) {
            return [
                'result' => false,
                'msg' => '模板生成失败'
            ];
        }

        $xacode = User::createXacode2($photographer_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $workName = "上海半岛酒店";
        $name = "朱迪 · 摄影作品";
        $money = "30000元 · 30张 · 20小时";
        $datas = [
            '##money##' => 1000,
            '##number##' => '1张',
            '##time##' => '20小时',
            '##customer##' => '上海半岛酒店',
            '##name##' => '朱迪',
            '##title##' => '摄影作品',
        ];

        $bg = $template->background . "?imageMogr2/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $writeBg = "https://file.zuopin.cloud/FjRG0YoL-6pTZ8lyjXbkoe4ZFddf";

        $handle[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($writeBg) . "/gravity/South/dx/0/dy/200/";
        $handle[] = "/image/" . $xacodeImgage . "/gravity/SouthEast/dx/180/dy/275/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($workName) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/960/fill/" . base64_urlSafeEncode("#323232") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/470/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($name) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/340/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($money) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/270/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看完整作品") . "/fontsize/600/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/86/";

        foreach ($datas as $key => $data) {
            $template->text1 = str_replace($key, $data, $template->text1);
            $template->text2 = str_replace($key, $data, $template->text2);
            $template->text3 = str_replace($key, $data, $template->text3);
            $template->text4 = str_replace($key, $data, $template->text4);
        }

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text1) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/100/dy/170/";
        if ($template->text2) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text2) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/100/dy/320/";
        }
        if ($template->text3) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text3) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/100/dy/470/";
        }

        if ($template->text4) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text4) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/100/dy/620/";
        }

        return [
            'result' => true,
            'msg' => implode($handle)
        ];
    }

    public function destroy($id)
    {
        $result = Templates::where('id', $id)->delete();
        return response()->json(compact('result'));
    }

    public function update(Request $request)
    {
        dd($request->all());
    }

    public function create()
    {
        return view('admin/templates/create');
    }


}
