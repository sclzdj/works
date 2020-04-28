<?php

namespace App\Http\Controllers\Api;

use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\Star;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\ErrLogServer;
use App\Servers\FileServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use function Qiniu\base64_urlSafeDecode;
use function Qiniu\base64_urlSafeEncode;
use Qiniu\Storage\UploadManager;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class StarController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function __construct()
    {

    }

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function getStars(Request $request)
    {
        $page = $request->input('page', -1);
        $size = $request->input('size', 15);
        if ($page == -1) {
            $photographer_ids = (new Star())
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->pluck('photographer_id');
        } else {
            $page = ($page - 1) * $size;
            $photographer_ids = (new Star())
                ->skip($page)
                ->take($size)
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->pluck('photographer_id');
        }

        $this->data['data'] = array();
        foreach ($photographer_ids as $photographer_id) {
            $photographer = Photographer::with(
                [
                    'photographerWorks' => function ($query) {
                        $query->where('status', 200);
                    },
                ]
            )
                ->where('photographers.id', $photographer_id)
                ->leftJoin('photographer_ranks', 'photographers.photographer_rank_id', '=', 'photographer_ranks.id')
                ->select(
                    [
                        'photographers.id',
                        'photographers.name',
                        'photographers.avatar',
                        'photographer_ranks.name as ranks',
                        'photographers.province',
                        'photographers.city',
                        'photographers.area',
                    ]
                )
                ->first();

            $this->data['data'][] = $photographer;
        }
        $fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            PhotographerWorkSource::allowFields()
        );
        foreach ($this->data['data'] as &$datum) {
            $datum['province'] = SystemArea::select(['id', 'name', 'short_name'])->where(
                'id',
                $datum['province']
            )->first();
            $datum['city'] = SystemArea::select(['id', 'name', 'short_name'])->where('id', $datum['city'])->first();
            $datum['area'] = SystemArea::select(['id', 'name', 'short_name'])->where('id', $datum['area'])->first();
            $work_limit = (int)$request->work_limit;
            if ($work_limit > 0) {
                $photographerWorks = PhotographerWork::select(PhotographerWork::allowFields())->where(
                    [
                        'photographer_id' =>$datum['id'],
                        'status' => 200,
                    ]
                )->orderBy('roof', 'desc')->orderBy(
                    'created_at',
                    'desc'
                )->orderBy('id', 'desc')->take(
                    $work_limit
                )->get();
                $all_tags = [];
                foreach ($photographerWorks as $_k => $photographerWork) {
                    $photographerWorkTags = $photographerWork->photographerWorkTags()->select(
                        PhotographerWorkTag::allowFields()
                    )->get()->toArray();
                    $all_tags[] = $photographerWorkTags;
                }
                $photographerWorks = $photographerWorks->toArray();
                $photographerWorks = ArrServer::toNullStrData(
                    $photographerWorks,
                    ['sheets_number', 'shooting_duration']
                );
                $photographerWorks = ArrServer::inData($photographerWorks, PhotographerWork::allowFields());
                foreach ($photographerWorks as $_k => $v) {
                    $photographerWorks[$_k]['tags'] = $all_tags[$_k];
                }
                $photographerWorks = SystemServer::parsePhotographerWorkCover($photographerWorks);
                $photographerWorks = SystemServer::parsePhotographerWorkCustomerIndustry($photographerWorks);
                $photographerWorks = SystemServer::parsePhotographerWorkCategory($photographerWorks);
                $datum['works'] = $photographerWorks;
            }
            $source_limit = (int)$request->source_limit;
            if ($source_limit > 0) {
                $photographerWorkSources = PhotographerWorkSource::select(
                    $fields
                )->join(
                    'photographer_works',
                    'photographer_work_sources.photographer_work_id',
                    '=',
                    'photographer_works.id'
                )->where(
                    [
                        'photographer_works.photographer_id' => $datum['id'],
                        'photographer_work_sources.status' => 200,
                        'photographer_works.status' => 200,
                        'photographer_work_sources.type' => 'image',
                    ]
                )->orderBy(
                    'photographer_works.roof',
                    'desc'
                )->orderBy(
                    'photographer_works.created_at',
                    'desc'
                )->orderBy(
                    'photographer_works.id',
                    'desc'
                )->orderBy(
                    'photographer_work_sources.sort',
                    'asc'
                )->take($source_limit)->get();
                $datum['sources'] = SystemServer::getPhotographerWorkSourcesThumb($photographerWorkSources);
            }
            unset($datum['photographerWorks']);
        }
        $this->data['result'] = true;
        $this->data['total'] = (new Star())->count();
        $this->data['last_page'] =  $page <= 0 ? 0 : ceil($this->data['total'] / $size);


        return $this->responseParseArray($this->data);
    }
    static public function calcWaterText($customer_name)
    {
        $fistX = 75;
        for ($i = 0; $i < mb_strlen($customer_name); $i++) {
            $char = mb_substr($customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 42;
            } else {
                $fistX += 26;
            }
        }

        return $fistX;
    }
    public function test(Request $request)
    {
//         $this->upload2();
//         die();
        $photographer_work_source_id = 1;
        $step = '水印图片持久请求';
        $photographerWorkSource = PhotographerWorkSource::where('id', $photographer_work_source_id)->first();
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();


        $srcKey = config('custom.qiniu.crop_work_source_image_bg');

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 生成水印图
        $xacode = PhotographerWork::getXacode($photographerWork->id);
        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/185x185!'
            );
        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/210x210!|roundPic/radius/!50p'
            );
        }

        // 计算出作品名的初始位置
        $fistX = self::calcWaterText($photographerWork->customer_name);
        // 水印剩余图片的数量和文字
        $count = PhotographerWorkSource::where(
            'photographer_work_id',
            $photographerWorkSource->photographer_work_id
        )->where('status', 200)->count();
        $text = $count - 1 <= 0 ? '微信扫一扫，看我的全部作品' : "微信扫一扫，看剩余".($count - 1)."张作品";

        $hanlde = [];
        $hanlde[] = 'https://file.zuopin.cloud/work_source_image_bg.jpg?';
        // 对原图进行加高处理 增加水印框架图位置
        $hanlde[] = "imageMogr2/auto-orient/thumbnail/1200x".($photographerWorkSource->deal_height + 230).'!';
        // 作品图
        if ($photographerWorkSource->deal_url) {
            $hanlde[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode(
                    $photographerWorkSource->deal_url
                )."/gravity/North/dx/0/dy/0/";
        }
        // 水印底部框架图
        $hanlde[] = "|watermark/3/image/".base64_encode(
                "https://file.zuopin.cloud/Fte_WqPqt7fBcyIsr2Lf_69VVhzK"
            )."/gravity/South/dx/0/dy/0/";
        // 水印小程序
        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";
        // 水印作品名
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographerWork->customer_name
            )."/fontsize/800/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/71/dy/162/";
        // 水印中的 @
        $hanlde[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode(
                "https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/".$fistX."/dy/170/";
        // 水印的用户名字
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($photographer->name)."/fontsize/800/fill/".base64_urlSafeEncode(
                "#C8C8C8"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/".($fistX + 45)."/dy/162/";
        // 水印最后一行 微信扫一扫
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($text)."/fontsize/609/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/78/";
        $hanlde[] = "|imageslim";

        $fops = implode($hanlde);

        echo $fops;


    }

    public function upload2()
    {
//        $filename = 'xacodes/' . time() . mt_rand(10000, 99999) . '.png';
//        $bgimg = Image::make('xacodes/bbg.jpg')->resize(383, 320);
//        $bgimg->save($filename);

        $filename = "images/弹窗@3x.png";

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        //用于签名的公钥和私钥
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传Token
        $upToken = $auth->uploadToken($bucket);
        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($upToken, null, $filename);

        dd($ret);
    }

    public function test2(Request $request)
    {
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 白背景图
        $whiteBg = $domain.'/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';
        // 黑背景图
        $blackBgs = [];
        $blackBg = $domain.'/FtXkbly4Qu-tEeiBiolLj-FFPXeo?imageMogr2/auto-orient/thumbnail/383x320!';
        $blackBgs = array_fill(0, 6, $blackBg);

        $photographer = $this->_photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $workIds = PhotographerWork::where('photographer_id', $request->photographer_id)
            ->where('status', 200)->get()->pluck('id');
        $resources = PhotographerWorkSource::where(['status' => 200])
            ->where('type', 'image')
            ->whereIn('photographer_work_id', $workIds)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
        $buttonText = SystemArea::find($photographer->province)->name.' · '.PhotographerRank::find(
                $photographer->photographer_rank_id
            )->name.'用户';

        $resourceId = 0;
        foreach ($resources as $key => $resource) {
            $resourceId = $resource->id;
            if ($resource->deal_width < $resource->deal_height) {  // 长图
                $width = 380;
                $height = $resource->deal_height;
                $imgs = $domain.'/'.$resource->deal_key."?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/382x320";
            } else { // 宽图
                $width = $resource->deal_width;
                $height = $resource->deal_height;
                $imgs = $domain.'/'.$resource->deal_key."?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/382x320";
            }

            $blackBgs[$key] = $imgs;
        }

        $handleUrl = array();
        $handleUrl[] = $whiteBg;
        $handleUrl[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode($blackBgs[0])."/gravity/NorthWest/dx/0/dy/0";
        $handleUrl[] = "/image/".\Qiniu\base64_urlSafeEncode($blackBgs[1])."/gravity/NorthWest/dx/409/dy/0";
        $handleUrl[] = "/image/".\Qiniu\base64_urlSafeEncode($blackBgs[2])."/gravity/NorthWest/dx/817/dy/0";
        $handleUrl[] = "/image/".\Qiniu\base64_urlSafeEncode($blackBgs[3])."/gravity/NorthWest/dx/0/dy/340";
        $handleUrl[] = "/image/".\Qiniu\base64_urlSafeEncode($blackBgs[4])."/gravity/NorthWest/dx/409/dy/340";
        $handleUrl[] = "/image/".\Qiniu\base64_urlSafeEncode($blackBgs[5])."/gravity/NorthWest/dx/817/dy/340";
        $handleUrl[] = "/text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1700/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/South/dx/0/dy/137";
        $handleUrl[] = "/text/".\Qiniu\base64_urlSafeEncode($buttonText)."/fontsize/1140/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/South/dx/0/dy/20";


        echo implode("", $handleUrl);
        die();
        array_shift($handleUrl);


        $fops = ["imageMogr2/auto-orient/thumbnail/1200x960!".implode("", $handleUrl)];
        $bucket = 'zuopin';
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            "FtSr3gPOeI8CjSgh5fBkeHaIsJnm",
            $fops,
            null,
            config(
                'app.url'
            ).'/api/notify/qiniu/fop?photographer_work_source_id='.$resourceId.'&step=5',
            true
        );
        var_dump($qrst);
    }

    public function test3(Request $request)
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $photographerWorkSource = PhotographerWorkSource::where(
            [
                'id' => $request->input('photographer_work_source_id'),
                'type' => 'image',
            ]
        )->first();
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();

        $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
        $xacode = PhotographerWork::getXacode($photographerWork->id);
        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/185x185!'
            );

        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/185x185!|roundPic/radius/!50p'
            );
        }

        $hanlde = [];
        $hanlde[] = "https://file.zuopin.cloud/work_source_image_bg.jpg";
        $hanlde[] = "?imageMogr2/auto-orient/crop/".$photographerWorkSource->deal_width.'x'.($photographerWorkSource->deal_height + 250);

        $hanlde[] = "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/";
        $hanlde[] = "|watermark/3/image/".base64_encode(
                "https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T"
            )."/gravity/South/dx/0/dy/0/";

        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";

        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographerWork->customer_name
            )."/fontsize/800/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/71/dy/162/";
        $fistX = 75;
        // 根据字体来判断宽度 中文40 数字字母20
        for ($i = 0; $i < mb_strlen($photographerWork->customer_name); $i++) {
            $char = mb_substr($photographerWork->customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 42;
            } else {
                $fistX += 26;
            }
        }

        $hanlde[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode(
                "https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/".$fistX."/dy/170/";
        $secondX = $fistX + 45;
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($photographer->name)."/fontsize/800/fill/".base64_urlSafeEncode(
                "#C8C8C8"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/".$secondX."/dy/162/";

        $count = PhotographerWorkSource::where(
            'photographer_work_id',
            $photographerWorkSource->photographer_work_id
        )->count();
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode(
                "微信扫一扫，看剩余".$count."张作品"
            )."/fontsize/609/fill/".base64_urlSafeEncode("#F7F7F7")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/SouthWest/dx/101/dy/80/";
        $hanlde[] = "|imageslim";
        echo implode($hanlde).PHP_EOL;
    }

    public function test4(Request $request)
    {
        $photographer_id = $request->input('photographer_id');
        $response = [];
        $photographer = $this->_photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

            return $response;
        }
        $user = User::where(['photographer_id' => $photographer_id])->first();
        if (!$user) {
            $response['code'] = 500;
            $response['msg'] = '微信用户不存在';

            return $response;
        }
        if ($user->identity != 1) {
            $response['code'] = 500;
            $response['msg'] = '微信用户不是用户';

            return $response;
        }

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $xacode = Photographer::getXacode($photographer_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'created_at',
            'desc'
        )->limit(4)->get()->toArray();

        $text = [];
        foreach ($photographer_works as $photographer_work) {
            $text[] = $photographer_work['customer_name'];
        }

        $data = [];
        $data['url1'] = $this->getPersonStyle1(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url2'] = $this->getPersonStyle2(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url3'] = $this->getPersonStyle3(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );

        return $data;
    }


    private function getPersonStyle1($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";
        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/141/dy/334/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/98/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/99/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/99/dy/90/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("Hi!")."/fontsize/2000/fill/".base64_urlSafeEncode(
                "#FFFFFF"
            )."/fontstyle/".base64_urlSafeEncode("Bold")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/NorthWest/dx/101/dy/180/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("我是用户")."/fontsize/2000/fill/".base64_urlSafeEncode(
                "#FFFFFF"
            )."/fontstyle/".base64_urlSafeEncode("Bold")."/font/".base64_urlSafeEncode(
                "Microsoft YaHei"
            )."/gravity/NorthWest/dx/101/dy/330/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/NorthWest/dx/101/dy/480/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                'Base'.$photographer_city
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/West/dx/101/dy/-220/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                '擅长'.$photographer_rank.'摄像'
            )."/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/West/dx/101/dy/-70/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle2($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {

        if ($photographer->bg_img) {
            $photographer->bg_img = $photographer->bg_img.'?imageMogr2/auto-orient/thumbnail/x1507/gravity/Center/crop/!1200x1507-0-0|imageslim';
        }

        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2187!";
        $handle = array();
        $handle[] = $bg;

        $handle[] = "|watermark/3/image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";
        if ($photographer->bg_img) {
            $handle[] = "image/".base64_urlSafeEncode($photographer->bg_img)."/gravity/North/dx/0/dy/0/";
        }

        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/90/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle3($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/".base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            )."/gravity/South/dx/0/dy/0/";
        $handle[] = "image/".$xacodeImgage."/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品")."/fontsize/720/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer->name
            )."/fontsize/1300/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographer_city.' · '.$photographer_rank.'用户'
            )."/fontsize/720/fill/".base64_urlSafeEncode("#646464")."/font/".base64_urlSafeEncode(
                "微软雅黑"
            )."/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode($footerFont)."/fontsize/720/fill/".base64_urlSafeEncode(
                "#969696"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/90/";
        $endKey = count($text);

        $indexPos = 180;
        foreach ($text as $key => $item) {
            $handle[] = "text/".\Qiniu\base64_urlSafeEncode($item).
                "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
                "/fontstyle/".base64_urlSafeEncode("Bold").
                "/font/".base64_urlSafeEncode("Microsoft YaHei").
                "/gravity/NorthWest/dx/100/dy/".($indexPos + ($key * 150))."/";
        }

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("……").
            "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
            "/fontstyle/".base64_urlSafeEncode("Bold").
            "/font/".base64_urlSafeEncode("Microsoft YaHei").
            "/gravity/NorthWest/dx/100/dy/".($indexPos + ($endKey * 160))."/";

        $handle[] = "text/".\Qiniu\base64_urlSafeEncode("都是我拍的").
            "/fontsize/2000/fill/".base64_urlSafeEncode("#FFFFFF").
            "/fontstyle/".base64_urlSafeEncode("Bold").
            "/font/".base64_urlSafeEncode("Microsoft YaHei").
            "/gravity/West/dx/100/dy/80/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

    /**
     * 主要针对前端webuploader插件不能识别http错误码
     *
     * @param       $message
     * @param int $status_code
     * @param array $data
     * @param array $headers
     * @param int $options
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function uploadResponse(
        $message,
        $status_code = 200,
        $data = [],
        $headers = [],
        $options = 0
    ) {
        $code = $status_code == 201 ?
            201 :
            200;

        return response()->json(
            [
                'message' => $message,
                'status_code' => $status_code,
                'data' => $data,
            ],
            $code,
            $headers,
            $options
        );
    }

    public function upload(Request $request, $path = 'uploads', $key = 'file')
    {
        $upload_type = (string)$request->instance()->post(
            'upload_type',
            (string)$request->instance()->get('upload_type', 'file')
        );
        $filename = (string)$request->instance()->post(
            'filename',
            urldecode((string)$request->instance()->get('filename', ''))
        );
        $scene = (string)$request->instance()->post('scene', (string)$request->instance()->get('scene', ''));
        $filename = ltrim(str_replace('\\', '/', $filename), '/');

        if ($filename === ''
            || in_array(
                $upload_type,
                [
                    'images',
                    'files',
                ]
            )) {
            $filename = date("Ymd/").time().mt_rand(10000, 99999);
        }
        if (!$request->file($key)) {
            if ($scene == 'ueditor_upload') {
                return response()->json(
                    [
                        "state" => '没有选择上传文件',
                    ]
                );
            }

            return $this->uploadResponse('没有选择上传文件', 400);
        }
        $iniSize = $request->file($key)->getMaxFilesize();
        if (!$request->hasFile($key)) {
            if ($scene == 'ueditor_upload') {
                return response()->json(
                    [
                        "state" => 'php.ini最大限制上传'.
                            number_format(
                                $iniSize /
                                1024 / 1024,
                                2,
                                '.',
                                ''
                            ).'M的文件',
                    ]
                );
            }

            return $this->uploadResponse(
                'php.ini最大限制上传'.
                number_format(
                    $iniSize /
                    1024 / 1024,
                    2,
                    '.',
                    ''
                ).'M的文件',
                400
            );
        }
        if (!$request->file($key)->isValid()) {
            if ($scene == 'ueditor_upload') {
                return response()->json(
                    [
                        "state" => '上传过程中出错，请主要检查php.ini是否配置正确',
                    ]
                );
            }

            return $this->uploadResponse('上传过程中出错，请主要检查php.ini是否配置正确', 400);
        }
        $fileInfo = [];
        $fileInfo['extension'] = $request->file($key)->clientExtension() !== '' ? $request->file($key)->clientExtension(
        ) : $request->file($key)->extension();
        $fileInfo['mimeType'] = $request->file($key)->getMimeType();
        $fileInfo['size'] = $request->file($key)->getSize();
        $fileInfo['iniSize'] = $iniSize;
        if ($fileInfo['size'] > $fileInfo['iniSize']) {
            if ($scene == 'ueditor_upload') {
                return response()->json(
                    [
                        "state" => 'php.ini最大限制上传'.
                            number_format(
                                $fileInfo['iniSize'] /
                                1024 / 1024,
                                2,
                                '.',
                                ''
                            ).'M的文件',
                    ]
                );
            }

            return $this->uploadResponse(
                'php.ini最大限制上传'.
                number_format(
                    $fileInfo['iniSize'] /
                    1024 / 1024,
                    2,
                    '.',
                    ''
                ).'M的文件',
                400
            );
        }
        if ($scene == '这里写你要判断的场景') {//这里是上传场景可以根据这个做一些特殊判断，下面写出对应的限制即可
            $upload_image_limit_size = '';
            $upload_image_allow_extension = '';
            $upload_file_limit_size = '';
            $upload_file_allow_extension = '';
        }
        $filetype = 'file';
        if (strpos($fileInfo['mimeType'], 'image/') !== false) {
            $filetype = 'image';
            $upload_image_limit_size = $upload_image_limit_size ?? SystemConfig::getVal(
                    'upload_image_limit_size',
                    'upload'
                );
            if ($upload_image_limit_size > 0
                && $fileInfo['size'] > $upload_image_limit_size * 1000
            ) {
                if ($scene == 'ueditor_upload') {
                    return response()->json(
                        [
                            "state" => '最大允许上传'.
                                $upload_image_limit_size.'K的图片',
                        ]
                    );
                }

                return $this->uploadResponse(
                    '最大允许上传'.
                    $upload_image_limit_size.'K的图片',
                    400
                );
            }
            $upload_image_allow_extension = $upload_image_allow_extension ?? SystemConfig::getVal(
                    'upload_image_allow_extension',
                    'upload'
                );
            if ($upload_image_allow_extension !== '') {
                $upload_image_allow_extension_arr =
                    explode(',', $upload_image_allow_extension);
                if (!in_array(
                    $fileInfo['extension'],
                    $upload_image_allow_extension_arr
                )
                ) {
                    if ($scene == 'ueditor_upload') {
                        return response()->json(
                            [
                                "state" => '只允许上传图片的后缀类型：'.
                                    $upload_image_allow_extension,
                            ]
                        );
                    }

                    return $this->uploadResponse(
                        '只允许上传图片的后缀类型：'.
                        $upload_image_allow_extension,
                        400
                    );
                }
            }
        } else {
            $upload_file_limit_size = $upload_file_limit_size ?? SystemConfig::getVal(
                    'upload_file_limit_size',
                    'upload'
                );
            if ($upload_file_limit_size > 0
                && $fileInfo['size'] > $upload_file_limit_size * 1000
            ) {
                if ($scene == 'ueditor_upload') {
                    return response()->json(
                        [
                            "state" => '最大允许上传'.
                                $upload_file_limit_size.'K的文件',
                        ]
                    );
                }

                return $this->uploadResponse(
                    '最大允许上传'.
                    $upload_file_limit_size.'K的文件',
                    400
                );
            }
            $upload_file_allow_extension = $upload_file_allow_extension ?? SystemConfig::getVal(
                    'upload_file_allow_extension',
                    'upload'
                );
            if ($upload_file_allow_extension !== '') {
                $upload_file_allow_extension_arr =
                    explode(',', $upload_file_allow_extension);
                if (!in_array(
                    $fileInfo['extension'],
                    $upload_file_allow_extension_arr
                )
                ) {
                    if ($scene == 'ueditor_upload') {
                        return response()->json(
                            [
                                "state" => "只允许上传文件的后缀类型",
                            ]
                        );
                    }

                    return $this->uploadResponse(
                        '只允许上传文件的后缀类型：'.
                        $upload_file_allow_extension,
                        400
                    );
                }
            }
        }
        $fileInfo['scene'] = $scene;
        \DB::beginTransaction();//开启事务
        $FileServer = new FileServer();
        try {
            if (request()->method() == 'OPTIONS') {
                return $this->response([]);
            }

            $url = $FileServer->upload($filetype, $filename, $path, $request->file($key), $fileInfo, $upload_type);
            if ($url !== false) {
                \DB::commit();//提交事务
                if ($scene == 'ueditor_upload') {
                    return response()->json(
                        [
                            "state" => "SUCCESS",
                            "url" => $url,
                            "title" => $url,
                            "original" => $url,
                        ]
                    );
                }


                $storagefilename = storage_path('app/public'.substr($url, 28));

                $bucket = 'zuopin';
                $buckets = config('custom.qiniu.buckets');
                $domain = $buckets[$bucket]['domain'] ?? '';
                //用于签名的公钥和私钥
                $accessKey = config('custom.qiniu.accessKey');
                $secretKey = config('custom.qiniu.secretKey');
                // 初始化签权对象
                $auth = new Auth($accessKey, $secretKey);
                // 生成上传Token
                $upToken = $auth->uploadToken($bucket);
                // 构建 UploadManager 对象
                $uploadMgr = new UploadManager();
                list($ret, $err) = $uploadMgr->putFile($upToken, null, $storagefilename);

                if (empty($err)) {
                    return $this->uploadResponse('上传成功', 201, ['url' => $domain.'/'.$ret['key']]);
                }

                return $this->uploadResponse('上传失败', 400);

            } else {
                \DB::rollback();//回滚事务
                $FileServer->delete($FileServer->objects);
                if ($scene == 'ueditor_upload') {
                    return response()->json(
                        [
                            "state" => "上传失败",
                        ]
                    );
                }

                return $this->uploadResponse('上传失败', 400);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务
            $FileServer->delete($FileServer->objects);
            if ($scene == 'ueditor_upload') {
                return response()->json(
                    [
                        "state" => $e->getMessage(),
                    ]
                );
            }

            return $this->eResponse($e->getMessage(), 500);
        }
    }

}
