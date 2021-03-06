<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\HelpTags;
use App\Model\Index\Invite;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
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
     * 邀请列表
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

        if (!empty($form['remark2'])) {
            $where[] = array("invite.remark2", $form['remark2']);
        }

        if (!empty($form['remark3'])) {
            $where[] = array("invite.remark3", $form['remark3']);
        }

        if (!empty($form['remark'])) {
            $where[] = array("invite.remark", 'like', '%' . $form['remark'] . '%');
        }


        if ($form['status'] != -1) {
            $where[] = array("invote_codes.status", $form['status']);
        }

        $orderBy = "created_at";
        if (!empty($form['orderBy'])) {
            $orderBy = $form['orderBy'];
        }

        $data = (new Invite())
            ->join('invote_codes', 'invote_codes.id', '=', 'invite.invite_id')
            ->leftJoin('users', 'invote_codes.user_id', '=', 'users.id')
            ->where($where)
            ->select([
                'invite.*', 'invote_codes.code', 'invote_codes.status',
                'users.nickname', 'users.id as user_id', 'users.photographer_id',
                \DB::raw('(select COUNT(*) FROM photographer_works WHERE `photographer_id` = users.photographer_id and status = 200) as `photographer_works_count`'),
                \DB::raw('(select COUNT(*) FROM photographer_work_sources
                 WHERE `photographer_work_id` in (select id FROM photographer_works WHERE `photographer_id` = users.photographer_id and status = 200) and status = 200)
                 as `photographer_works_resource_count`'),
                \DB::raw("(select count(*) from visitors where `photographer_id` = users.photographer_id  ) as photographer_works_visit_count")
            ])
            ->skip($page)
            ->take($size)
            ->orderBy($orderBy, 'desc')
            ->get();

        // dd($data);

        $count = (new Invite())
            ->where($where)
            ->join('invote_codes', 'invote_codes.id', '=', 'invite.invite_id')
            ->leftJoin('users', 'invote_codes.user_id', '=', 'users.id')
            ->count();

        return response()->json(compact('data', 'count'));
    }

    public function store(Request $request)
    {
        $inviteCode = InvoteCode::createInvote();

        $invite = new Invite();
        $invite->invite_id = $inviteCode;
        $invite->remark = "";
        $invite->created_at = date('Y-m-d H:i:s');
        $result = $invite->save();

        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Invite::where('id', $id)->delete();
        return response()->json(compact('result'));
    }

    public function create()
    {
        return view('admin/helptags/create');
    }

    public function update(Request $request)
    {

        $action = $request->input('action');
        if ($action == "remark") {
            $data = $request->input('data');
            $result = Invite::where('id', $data['id'])->update([
                'remark' => $data['remark']
            ]);
        }
        if ($action == "remark2") {
            $data = $request->input('data');
            $result = Invite::where('id', $data['id'])->update([
                'remark2' => $data['remark2']
            ]);
        }

        if ($action == "remark3") {
            $data = $request->input('data');
            $result = Invite::where('id', $data['id'])->update([
                'remark3' => $data['remark3']
            ]);
        }


        $msg = "";
        return response()->json(compact('result', 'msg'));

    }

    public function getsuggestwork(Request $request){
        $works = PhotographerWork::where(['status' => 200])->get()->toArray();
//        $source
        foreach ($works as $work){
//            PhotographerWorkSource
        }
    }
}
