<?php

namespace App\Http\Controllers\Admin\Question;

use App\Http\Controllers\Admin\BaseController;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/question/index');
    }

}
