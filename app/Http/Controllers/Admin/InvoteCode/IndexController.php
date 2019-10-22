<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/invotecode/index');
    }

    public function show()
    {
        return view('admin/invotecode/show');
    }
}
