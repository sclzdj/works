<?php

namespace App\Http\Requests\Index;

use App\Http\Requests\BaseRequest;
use App\Rules\ValidateWordSecurity;
use App\Rules\ValidationName;

class PhotographerGatherRequest extends BaseRequest
{
    /**
     * 规则
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        switch ($this->getScene()) {
            case 'index':
                $rules = [
                    'photographer_id' => 'integer',
                ];
                $rules = array_merge($rules, $this->predefined['paginate']['rules']);
                break;
            case 'show':
                $rules = [
                    'photographer_gather_id' => 'integer|exists:photographer_gathers,id',
                ];
                break;
            case 'store':
                $rules = [
//                    'name' => ['required', new ValidationName, new ValidateWordSecurity],
                    //创建合集暂时不需要添加项目
//                    'photographer_gather_info_id' => 'required|exists:photographer_gather_infos,id',
//                    'photographer_work_ids' => 'required|array',
                ];
                break;
            case 'update':
                $rules = [
//                    'photographer_gather_id' => 'required',
//                    'name' => 'required',
//                    'photographer_gather_info_id' => 'required|exists:photographer_gather_infos,id',
//                    'photographer_work_ids' => 'required|array',
                ];
                break;
            case 'infoIndex':
                $rules = [

                ];
                break;
            case 'infoStore':
                $rules = [
//                    'photographer_rank_id' => 'required|exists:photographer_ranks,id',
//                    'start_year' => 'required|digits:4',
////                    'is_default' => 'in:0,1',
//                    'brand_tags' => 'required|array',
//                    'brand_tags.*' => 'required|max:50',
                ];
                break;
            case 'infoUpdate':
                $rules = [
//                    'photographer_gather_info_id' => 'required|exists:photographer_gather_infos,id',
//                    'photographer_rank_id' => 'required|exists:photographer_ranks,id',
//                    'start_year' => 'required|digits:4',
////                    'is_default' => 'in:0,1',
//                    'showtype' => 'integer',
//                    'brand_tags' => 'required|array',
//                    'brand_tags.*' => 'required|max:50',
                ];
                break;
            case 'infoSetDefault':
                $rules = [
                    'photographer_gather_info_id' => 'required|exists:photographer_gather_infos,id',
                    'is_default' => 'required|in:0,1',
                ];
                break;
            case 'info':
                $rules = [
                    'photographer_gather_info_id' => 'required|exists:photographer_gather_infos,id',
                ];
                break;
            case 'getallsource':
                $rules = [
                    'photographer_gather_id' => 'required|exists:photographer_gathers,id',
                ];
                break;
        }

        return $rules;
    }

    /**
     * 提示信息
     *
     * @return array
     */
    public function messages()
    {
        $messages = [];
        switch ($this->getScene()) {
            case 'index':
                $messages = [
                    'photographer_id.integer' => '用户id必须为数字',
                ];
                $messages = array_merge($messages, $this->predefined['paginate']['messages']);
                break;
            case 'show':
                $messages = [
                    'photographer_gather_id.integer' => '合集id不存在！',
                ];
                break;
            case 'store':
                $messages = [
                    'name.required' => '合集名称不能为空',
                    'photographer_gather_info_id.required' => '合集资料id不能为空',
                    'photographer_gather_info_id.exists' => '合集资料不存在',
                    'photographer_work_ids.required' => '项目id集合不能为空',
                    'photographer_work_ids.array' => '项目id集合必须为数组',

                ];
                break;
            case 'update':
                $messages = [
                    'photographer_gather_id.required' => '合集id不能为空',
                    'photographer_gather_id.exists' => '合集不存在',
                    'name.required' => '合集名称不能为空',
                    'photographer_gather_info_id.required' => '合集资料id不能为空',
                    'photographer_gather_info_id.exists' => '合集资料不存在',
                    'photographer_work_ids.required' => '项目id集合不能为空',
                    'photographer_work_ids.array' => '项目id集合必须为数组',

                ];
                break;
            case 'infoIndex':
                $messages = [

                ];
                break;
            case 'getallsource':
                $messages = [
                    'photographer_gather_id.required' => '合集id不能为空',
                    'photographer_gather_id.exists' => '合集不存在',
                ];
                break;
            case 'infoStore':
                $messages = [
                    'photographer_rank_id.required' => '用户头衔id不能为空',
                    'photographer_rank_id.exists' => '用户头衔不存在',
                    'start_year.required' => '起始年份不能为空',
                    'start_year.digits' => '起始年份格式错误',
                    'is_default.in' => '是否设为默认传递错误',
                    'brand_tags.required' => '品牌标签必须传递',
                    'brand_tags.array' => '品牌必须是数组',
                    'brand_tags.*.required' => '品牌标签不能为空',
                    'brand_tags.*.max' => '品牌标签名称长度最大为50',
                ];
                break;
            case 'infoUpdate':
                $messages = [
                    'photographer_gather_info_id.required' => '合集资料id不能为空',
                    'photographer_gather_info_id.exists' => '合集资料不存在',
                    'photographer_rank_id.required' => '用户头衔不能为空',
                    'photographer_rank_id.exists' => '用户头衔不存在',
                    'start_year.required' => '起始年份不能为空',
                    'start_year.digits' => '起始年份格式错误',
                    'is_default.in' => '是否设为默认传递错误',
                    'brand_tags.array' => '品牌必须是数组',
                    'brand_tags.*.required' => '品牌标签不能为空',
                    'brand_tags.*.max' => '品牌标签名称长度最大为50',
                ];
                break;
            case 'infoSetDefault':
                $messages = [
                    'photographer_gather_info_id.required' => '合集资料id不能为空',
                    'photographer_gather_info_id.exists' => '合集资料不存在',
                    'is_default.required' => '是否设为默认不能为空',
                    'is_default.in' => '是否设为默认传递错误',
                ];
                break;
            case 'info':
                $messages = [
                    'photographer_gather_info_id.required' => '合集资料id不能为空',
                    'photographer_gather_info_id.exists' => '合集资料不存在',
                ];
                break;
        }

        return $messages;
    }

    /**
     * 场景配置
     *
     * @return array
     */
    public function scenes()
    {
        return [
            'index' => ['GET|App\Http\Controllers\Api\PhotographerGatherController@index'],
            'store' => ['POST|App\Http\Controllers\Api\PhotographerGatherController@store'],
            'update' => ['POST|App\Http\Controllers\Api\PhotographerGatherController@update'],
            'infoIndex' => ['GET|App\Http\Controllers\Api\PhotographerGatherInfoController@index'],
            'infoStore' => ['POST|App\Http\Controllers\Api\PhotographerGatherInfoController@store'],
            'infoUpdate' => ['POST|App\Http\Controllers\Api\PhotographerGatherInfoController@update'],
            'infoSetDefault' => ['POST|App\Http\Controllers\Api\PhotographerGatherInfoController@setDefault'],
            'show' => [
                'POST|App\Http\Controllers\Api\PhotographerGatherInfoController@show',
                'POST|App\Http\Controllers\Api\PhotographerGatherInfoController@modifyrank'
            ],
            'getallsource' => ['GET|App\Http\Controllers\Api\PhotographerGatherInfoController@getallsource'],
            'info' => [
                'GET|App\Http\Controllers\Api\PhotographerGatherInfoController@show',
                'DELETE|App\Http\Controllers\Api\PhotographerGatherInfoController@destroy',
                'POST|App\Http\Controllers\Api\PhotographerGatherInfoController@copy',
            ],
        ];
    }
}
