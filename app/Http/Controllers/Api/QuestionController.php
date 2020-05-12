<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\QuestionRequest;

class QuestionController extends UserGuardController
{
    public function collect(QuestionRequest $request)
    {
        try {
            $validated = $request->validated();




        } catch (\Exception $exception) {


        }

    }
}
