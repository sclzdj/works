<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/invotecode/index');
    }

    public function lists()
    {
        return view('admin/invotecode/show');
    }
}
