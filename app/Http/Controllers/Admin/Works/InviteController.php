<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\HelpTags;
use App\Model\Index\Invite;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\Question;
use App\Model\Index\Star;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InviteController extends BaseController
{
    /**
     * 问题反馈
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/invite/index');
    }

    /**
     * 大咖列表
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $page = $request->input('page', 1);
        $form = $request->input('form');
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];


        if (isset($form['created_at'][0])) {
            $where[] = array("invite.created_at", ">=", $form['created_at'][0] . ' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("invite.created_at", "<=", $form['created_at'][1] . ' 23:59:59');
        }


        $data = (new Invite())
            ->where($where)
            ->join('invote_codes' , 'invote_codes.id' , '=' , 'invite.invite_id')
            ->leftJoin('users' , 'invote_codes.user_id' , '=' , 'users.id')
            ->select(['invite.*' , 'invote_codes.code' , 'invote_codes.status' , 'users.nickname' ])
            ->skip($page)
            ->take($size)
            ->get();



        $count = (new Invite())->count();

        return response()->json(compact('data', 'count'));
    }

    public function store(Request $request)
    {
        $inviteCode = InvoteCode::createInvote();

        $invite = new Invite();
        $invite->invite_id = $inviteCode;
        $invite->remark = "";
        $invite->created_at =  date('Y-m-d H:i:s');
        $result = $invite->save();

        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = HelpTags::where('id', $id)->delete();
        return response()->json(compact('result'));
    }

    public function create()
    {
        return view('admin/helptags/create');
    }

    public function update(Request $request) {

        $data = $request->input('form');
        $result = HelpTags::where('id', $data['id'])->update([
            'name' => $data['name']
        ]);
        $msg = "";
        return response()->json(compact('result', 'msg'));

    }

}
