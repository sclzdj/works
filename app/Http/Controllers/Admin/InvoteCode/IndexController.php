<?php

namespace App\Http\Controllers\Admin\InvoteCode;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\InvoteCode;


class IndexController extends BaseController
{
    public function index()
    {
        return view('admin/invotecode/index');
    }

    public function lists()
    {
        $data = InvoteCode::all();
        return response()->json(compact( 'data'));
    }
}
