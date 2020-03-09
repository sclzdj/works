<?php

use Illuminate\Database\Seeder;

class HelpNotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Model\Index\HelpNote::create(
            [
                'title' => '为何拓展人脉？',
                'content' => '<p>人脉是用户最重要的资源，有人脉才有客户。</p><p><br/></p><p>才有收入。</p>',
                'status'=>200
            ]
        );
        \App\Model\Index\HelpNote::create(
            [
                'title' => '如何拓展人脉？',
                'content' => '打开我的图库，把水印海报、水印照片转发到朋友圈、微博、微信。特别是海报，吸引粉丝扫码。',
                'status'=>200
            ]
        );
        \App\Model\Index\HelpNote::create(
            [
                'title' => '遇到问题怎么办？',
                'content' => '<p>点击右下角的原型按钮联系客服。但是不要随便使用这个功能，因为接电话的是老板。</p><p>帮助文档很详细。</p><p><br/></p><p><br/></p><p>你可以试着先搜一搜。<br/></p>',
                'status'=>200
            ]
        );
    }
}
