<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\Photographer;
use App\Model\Index\Question;
use App\Model\Index\QuestionUser;
use App\Model\Index\Star;
use App\Model\Index\User;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends BaseController
{
    /**
     * 问题反馈
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin/question/index');
    }

    public function create()
    {
        return view('admin/question/create', compact('userstr'));
    }

    public function update(Request $request)
    {
        $data = $request->input('form');
        if (isset($data['method']) && $data['method'] == "merge") {
            $ids = $data['ids'];
            $allIds = $data['ids'];
            // 先获取所有问题
            $questions = Question::with(['QuestionUserRelation'])
                ->whereIn('id', $ids)->get()->toArray();
            // 只留下第一个问题
            $firstQuestionId = array_shift($ids);
            $allUserId = [];
            // 获取所有需要合并的用户
            foreach ($questions as $question) {
                if (!empty($question['user_id']))
                    $allUserId[] = $question['user_id'];

                foreach ($question['question_user_relation'] as $item) {
                    if ($item['id']) {
                        $allUserId[] = $item['id'];
                    }
                }
            }

            //dd(array_unique($allUserId));
            // 删除掉以前问题的用户
            QuestionUser::where('question_id', $firstQuestionId)->delete();
            // 删除掉合并的问题
            Question::whereIn('id', $ids)->update([
                'status' => 5
            ]);
            foreach (array_unique($allUserId) as $item) {
                QuestionUser::insert([
                    'question_id' => $firstQuestionId,
                    'user_id' => $item,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } else {
            $important = 0;
            if ($data['important'] == "true")
                $important = 1;

            $result = Question::where('id', $data['id'])->update([
                'status' => $data['status'],
                'content' => $data['content'],
                'important' => $important,
            ]);

            $msg = "";
            return response()->json(compact('result', 'msg'));
        }
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
        $isRenling = $request->input('type');

        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];
//        if ($form['type'] != 0) {
//            $where[] = ['question.type', $form['type']];
//        }
        $where[] = ['question.type', 1];
        if ($form['status'] != -1) {
            $where[] = ['question.status', $form['status']];
        } else {
            $where[] = ['question.status', '!=', 4];
            $where[] = ['question.status', '!=', 5];
        }

        if ($form['page'] != "选择页面") {
            $where[] = ['question.page', $form['page']];
        }

        if ($form['keyword'] != "") {
            $where[] = ['question.content', 'like', "%{$form['keyword']}%"];
        }

        if (isset($form['created_at'][0])) {
            $where[] = array("question.created_at", ">=", $form['created_at'][0] . ' 00:00:01');
        }

        if (isset($form['created_at'][1])) {
            $where[] = array("question.created_at", "<=", $form['created_at'][1] . ' 23:59:59');
        }

        if ($isRenling == 1) {
            //   DB::connection()->enableQueryLog();
            $data = (new Question())
                ->with(['QuestionUserRelation:nickname'])
                ->where($where)
                ->skip($page)->take($size)
                ->leftJoin('users', 'users.id', '=', 'question.user_id')
                ->select('question.*', 'users.nickname')
                ->selectRaw("(select COUNT(*) from question_user where question_user.question_id = question.id) as question_user_count")
                ->havingRaw("question_user_count > ? OR question.important = ?", [1, 1])
                ->orderBy('question.important', 'desc')
                ->orderBy('question_user_count', 'desc')
                ->get()->toArray();

            //dd(DB::getQueryLog());
        } else {
            $data = (new Question())
                ->with(['QuestionUserRelation:nickname'])
                ->where($where)
                ->skip($page)->take($size)
                ->leftJoin('users', 'users.id', '=', 'question.user_id')
                ->select('question.*', 'users.nickname')
                ->orderBy('question.important', 'desc')
                ->orderBy('question.created_at', 'desc')
                ->get()
                ->toArray();
        }

        foreach ($data as &$datum) {
            $created_at = Carbon::createFromTimeString($datum['created_at']);
            $diff = Carbon::createFromTimestamp(time())->diff($created_at);
            $datum['diffNowTime'] = sprintf("%d天", $diff->days);

            $updated_at = Carbon::createFromTimeString($datum['updated_at']);
            $diff2 = Carbon::createFromTimestamp(time())->diff($updated_at);
            $datum['diffEditTime'] = sprintf("%d天", $diff2->days);

            if (count($datum['question_user_relation']) > 0) {
                $datum['nickname'] = implode(',', array_column($datum['question_user_relation'], 'nickname'));
            }

        }

        $count = (new Question())->where($where)->count();
        $user = User::all()->pluck("nickname", 'id');
        return response()->json(compact('data', 'count', 'user'));
    }

    public function edit($id)
    {
        return view('admin/question/edit', compact('id'));
    }

    public function show($id)
    {
        return response()->json([
            'msg' => (new Question())
                ->where('question.id', $id)
                ->join('users', 'users.id', '=', 'question.user_id')
                ->select('question.*', 'users.nickname')
                ->first()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->input('form');
        $important = 0;
        if ($data['important'] == "true")
            $important = 1;

        if (isset($data['attachment'])) {
            $data['attachment'] = json_encode($data['attachment']);
        } else {
            $data['attachment'] = json_encode([]);
        }

        $users = [];
        if (isset($data['users'])) {
            $users = $data['users'];
            unset($data['users']);
        }

        $data['important'] = $important;
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $result = Question::insert($data);

        if (count($users) > 0) {
            $questionId = \DB::connection()->getPdo()->lastInsertId();
            foreach ($users as $user) {
                QuestionUser::insert([
                    'question_id' => $questionId,
                    'user_id' => $user,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Question::where('id', $id)->delete();
        QuestionUser::where('question_id' , $id)->delete();
        return response()->json(compact('result'));
    }

    public function export(Request $request)
    {
        try {
            $form = json_decode($request->input('params'), 1);

            $fileName = time() . '.csv';
            $response = new StreamedResponse(null, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

            $response->setCallback((function () use ($form) {
                try {
                    $out = fopen('php://output', 'w');
                    fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // 添加 BOM
                    $firstWrite = true;
                    fputcsv($out, ['状态', '类型', '页面', '用户', '创建时间', '内容', '附件图片']);

                    $where = [];
                    if ($form['type'] != 0) {
                        $where[] = ['question.type', $form['type']];
                    }
                    if ($form['status'] != -1) {
                        $where[] = ['question.status', $form['status']];
                    }
                    if ($form['page'] != "选择页面") {
                        $where[] = ['question.page', $form['page']];
                    }
                    if (isset($form['created_at'][0])) {
                        $where[] = array("question.created_at", ">=", $form['created_at'][0] . ' 00:00:01');
                    }
                    if (isset($form['created_at'][1])) {
                        $where[] = array("question.created_at", "<=", $form['created_at'][1] . ' 23:59:59');
                    }
                    if (!empty($form['multipleSelection'])) {
                        $questions = Question::where($where)
                            ->whereIn('question.id', $form['multipleSelection'])
                            ->join('users', 'users.id', '=', 'question.user_id')
                            ->select('question.*', 'users.nickname')
                            ->get();
                    } else {
                        $questions = Question::where($where)
                            ->leftJoin('users', 'users.id', '=', 'question.user_id')
                            ->select('question.*', 'users.nickname')
                            ->get();
                    }
                    foreach ($questions as $question) {
                        $type = "";
                        switch ($question->type) {
                            case 1:
                                $type = "bug";
                                break;
                            case 2:
                                $type = "建议";
                                break;
                            default:
                                break;
                        }
                        $status = "";
                        switch ($question->status) {
                            case 0:
                                $status = "未处理";
                                break;
                            case 1:
                                $status = "待处理";
                                break;
                            case 2:
                                $status = "已解决";
                                break;
                        }

                        if ($question->attachment && json_decode($question->attachment, 1)) {
                            $attachment = json_decode($question->attachment, 1);
                            $attachment = array_column($attachment, 'value');
                            $attachment = implode("|", $attachment);
                        } else {
                            $attachment = "";
                        }

                        fputcsv($out,
                            [
                                $status,
                                $type,
                                $question->page,
                                $question->nickname,
                                $question->created_at,
                                $question->content,
                                $attachment
                            ]
                        );
                    }

                    fclose($out);
                } catch (\Exception $exception) {
                    echo $exception->getMessage();
                    echo $exception->getLine();
                }
            }));


            $response->send();
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }

}
