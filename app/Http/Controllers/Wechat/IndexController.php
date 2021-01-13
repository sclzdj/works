<?php
namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Model\Index\InviteList;
use App\Model\Index\OrderInfo;
use App\Model\Index\Photographer;
use EasyWeChat\Kernel\Messages\Text;
use Log;
use Illuminate\Support\Facades\Redis;
use App\Model\Index\User;

class IndexController extends Controller
{
    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function index(){
        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
        $app = app('wechat.official_account');
        $app->server->push(function($message){
            switch ($message['MsgType']) {
                case 'event':
                    $event = $message['Event'];
                    if (in_array($event, array('subscribe', 'SCAN'))
                        && (isset($message['EventKey']) && strpos($message['EventKey'], 'qrcode_login') !== false)) {//web端微信扫码事件
                        $reply = $this->QRCodeLoginEventHandle($message);
                        if ($reply) {
                            return $reply;
                            break;
                        }
                    }
                case 'text':
                    return $this->Message($message['Content']);
                    break;
                default:
                    return "";
                    break;
            }
        });

        return $app->server->serve();
    }

    private function Message($message){
        if ($message == '战报'){
            $allusercount = User::where(['identity' => 1])->count();
            $allpayuser = Photographer::where('level', '!=', 0)->count();
            $allinviteuser = InviteList::count();
            $allmoney = OrderInfo::where(['status' => 1])->sum('money');

            $today_bagin = date("Y-m-d");
            $today_end = date("Y-m-d",strtotime("+1 day"));
            $yestoday = date("Y-m-d",strtotime("-1 day"));

            $todayuser = User::where(['identity' => 1])->whereBetween('created_at', [$today_bagin, $today_end])->count();
            $todayinviteuser = InviteList::whereBetween('created_at', [$today_bagin, $today_end])->count();
            $today_pay = OrderInfo::where(['status' => 1])->whereBetween('created_at', [$today_bagin, $today_end])->count();
            $today_money = OrderInfo::where(['status' => 1])->whereBetween('created_at', [$today_bagin, $today_end])->sum('money');


            $yestodayuser = User::where(['identity' => 1])->whereBetween('created_at', [$yestoday, $today_bagin])->count();
            $yestodayinviteuser = InviteList::whereBetween('created_at', [$yestoday, $today_bagin])->count();
            $yestoday_pay = OrderInfo::where(['status' => 1])->whereBetween('created_at', [$yestoday, $today_bagin])->count();
            $yestoday_money = OrderInfo::where(['status' => 1])->whereBetween('created_at', [$yestoday, $today_bagin])->sum('money');


            //邀请
            $invite_ranks = InviteList::whereBetween('invite_list.created_at', [$today_bagin, $today_end])->select(
                'parent_photographer_id',
                'photographers.name',
                \DB::raw("count(*) as invite_count")
            )->join('photographers', 'photographers.id', '=', 'invite_list.parent_photographer_id')->groupBy('parent_photographer_id')->orderBy('invite_count', 'desc')->limit(5)->get();
            $html = "";
            foreach ($invite_ranks as $invite_rank){
                $html .= $invite_rank->name . '：' . $invite_rank->invite_count . " 人\n";
            }

            //统计邀请的人的金额排名
            $pay_ranks = \DB::select("SELECT a.*,photographers.`name` from (SELECT
`invite_list`.`parent_photographer_id`,
	( SELECT sum( money ) FROM order_info WHERE pay_id = users.id AND STATUS = 1 ) AS pay_money
FROM
	`invite_list`
	INNER JOIN `users` ON `users`.`photographer_id` = `invite_list`.`photographer_id`
WHERE
	`invite_list`.`created_at` BETWEEN '$today_bagin'
	AND  '$today_end'
ORDER BY
	`pay_money` DESC
	LIMIT 5) a INNER JOIN photographers ON photographers.id=a.parent_photographer_id where a.pay_money is not NULL
	");

            //统计邀请人付费人数排名
            $invite_pay_ranks = \DB::select("

	SELECT a.parent_photographer_id,count(photographer_id) as usercount,photographers.name from (
SELECT
	`invite_list`.`parent_photographer_id`,
	`invite_list`.`photographer_id`,
	`order_info`.`id`
FROM
	`invite_list`
	INNER JOIN `users` ON `users`.`photographer_id` = `invite_list`.`photographer_id`
	INNER JOIN `order_info` ON `order_info`.`pay_id` = `users`.`id`
WHERE
	`invite_list`.`created_at` BETWEEN '$today_bagin'
	AND '$today_end'
	AND (
	`order_info`.`status` = 1) GROUP BY 	`invite_list`.`photographer_id`
)a INNER JOIN photographers ON photographers.id=a.parent_photographer_id  GROUP BY a.parent_photographer_id");
            $html2 = "";
            foreach ($invite_pay_ranks as $invite_pay_rank){
                $html2 .= $invite_pay_rank->name . '：' . $invite_pay_rank->usercount . " 人\n";
            }

            //邀请
            $invite_ranks = InviteList::whereBetween('invite_list.created_at', [$yestoday, $today_bagin])->select(
                'parent_photographer_id',
                'photographers.name',
                \DB::raw("count(*) as invite_count")
            )->join('photographers', 'photographers.id', '=', 'invite_list.parent_photographer_id')->groupBy('parent_photographer_id')->orderBy('invite_count', 'desc')->limit(5)->get();
            $html3 = "";
            foreach ($invite_ranks as $invite_rank){
                $html3 .= $invite_rank->name . '：' . $invite_rank->invite_count . " 人\n";
            }

            //统计昨日邀请人付费人数排名
            $invite_pay_ranks = \DB::select("

	SELECT a.parent_photographer_id,count(photographer_id) as usercount,photographers.name from (
SELECT
	`invite_list`.`parent_photographer_id`,
	`invite_list`.`photographer_id`,
	`order_info`.`id`
FROM
	`invite_list`
	INNER JOIN `users` ON `users`.`photographer_id` = `invite_list`.`photographer_id`
	INNER JOIN `order_info` ON `order_info`.`pay_id` = `users`.`id`
WHERE
	`invite_list`.`created_at` BETWEEN '$yestoday'
	AND '$today_bagin'
	AND (
	`order_info`.`status` = 1) GROUP BY 	`invite_list`.`photographer_id`
)a INNER JOIN photographers ON photographers.id=a.parent_photographer_id  GROUP BY a.parent_photographer_id");
            $html4 = "";
            foreach ($invite_pay_ranks as $invite_pay_rank){
                $html4 .= $invite_pay_rank->name . '：' . $invite_pay_rank->usercount . " 人\n";
            }

            $return = <<<EOF
云作品为你播报

累计全部用户：$allusercount 人
累计受邀用户：$allinviteuser 人
累计付费用户：$allpayuser 人
累计付费金额：$allmoney 元

今日统计
新增全部用户：$todayuser 人
新增受邀用户：$todayinviteuser 人
新增付费用户：$today_pay 人
新增付费金额：$today_money 元

今日KOL邀请排名
$html

今日KOL销售排名
$html2

昨日统计
新增全部用户：$yestodayuser 人
新增受邀用户：$yestodayinviteuser 人
新增付费用户：$yestoday_pay 人
新增付费金额：$yestoday_money 人

昨日邀请TOP5
$html3

昨日销售TOP5
$html4


EOF;
            return new Text($return);
        }
    }

    /**
     * web端微信扫码登录事件处理
     * @param $message
     * @return string
     * @author jsyzchenchen@gmail.com
     * @date 2020/7/14
     */
    public function QRCodeLoginEventHandle($message)
    {
        $openid = $message['FromUserName'];
        $eventKey = $message['EventKey'];

        //获取微信用户信息
        $app = app('wechat.official_account');
        $wechatUser = $app->user->get($openid);
        if (isset($wechatUser['errcode']) && $wechatUser['errcode'] != 0) {
            Log::warning("webQRLoginEventHandle failed, wechat errmsg:" . $wechatUser['errmsg']);
            return "登录失败，请稍后重试！";
        }
        $unionid = $wechatUser['unionid'];

        //根据openid查询user表是否有该用户，如果没有新建用户，如果有将该用户的微信扫码登录状态置为已登录
        $user = User::where('gh_openid', $openid)->orWhere('unionid', $unionid)->first();
        if (empty($user)) {
            Log::warning("登录失败，请在云作品小程序注册并且绑定微信号。");
            return "登录失败，请在云作品小程序注册并且绑定微信号。";
        }

        //存储到Redis
        $res = Redis::setex($eventKey, 3600, $user->id);
        if (!$res) {
            Log::warning("qrcode_login redis set failed");
            return "服务异常，请稍后重试";
        }

        return "登录成功！";
    }
}
