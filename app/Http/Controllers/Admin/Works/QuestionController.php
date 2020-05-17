<?php


namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Index\Photographer;
use App\Model\Index\Question;
use App\Model\Index\Star;
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


        $data = (new Question())
            ->where($where)
            ->skip($page)->take($size)
            ->join('users', 'users.id', '=', 'question.user_id')
            ->select('question.*', 'users.nickname')
            ->get();

        $count = (new Star())->count();

        return response()->json(compact('data', 'count'));
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
        $result = Question::where('id', $data['id'])->update([
            'status' => $data['status']
        ]);
        $msg = "";
        return response()->json(compact('result', 'msg'));
    }

    public function destroy($id)
    {
        $result = Star::where('photographer_id', $id)->delete();
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
                        ->whereIn()
                        ->join('users', 'users.id', '=', 'question.user_id')
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

                    if ($question->attachment) {
                        $attachment = json_decode($question->attachment, 1);
                        $attachment = implode("   ", $attachment);
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
            }));

            $response->send();
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }

}
