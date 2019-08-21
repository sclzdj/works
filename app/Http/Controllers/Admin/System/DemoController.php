<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\BaseController;
use App\Model\Admin\SystemDemo;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

class DemoController extends BaseController {

    public function select2() {
        $select21 = (string)SystemDemo::where('name', 'demo_select2_1')->value('value');
        $select22 = (string)SystemDemo::where('name', 'demo_select2_2')->value('value');

        return view('/admin/system/demo/select2', compact('select21', 'select22'));
    }

    public function select2Save(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            $select21 = SystemDemo::where('name', 'demo_select2_1')->first();
            if (!$select21) {
                SystemDemo::create(['name'  => 'demo_select2_1',
                                    'value' => $data['demo_select2_1'],
                  ]
                );
            } else {
                $select21->value = $data['demo_select2_1'];
                $select21->save();
            }
            $select22 = SystemDemo::where('name', 'demo_select2_2')->first();
            if (!$select22) {
                SystemDemo::create(['name'  => 'demo_select2_2',
                                    'value' => $data['demo_select2_2'],
                  ]
                );
            } else {
                $select22->value = $data['demo_select2_2'];
                $select22->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }
    public function tags() {
        $tags1 = (string)SystemDemo::where('name', 'demo_tags_1')->value('value');
        $tags2 = (string)SystemDemo::where('name', 'demo_tags_2')->value('value');

        return view('/admin/system/demo/tags', compact('tags1', 'tags2'));
    }

    public function tagsSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            $tags1 = SystemDemo::where('name', 'demo_tags_1')->first();
            if (!$tags1) {
                SystemDemo::create(['name'  => 'demo_tags_1',
                                    'value' => $data['demo_tags_1'],
                ]
                );
            } else {
                $tags1->value = $data['demo_tags_1'];
                $tags1->save();
            }
            $tags2 = SystemDemo::where('name', 'demo_tags_2')->first();
            if (!$tags2) {
                SystemDemo::create(['name'  => 'demo_tags_2',
                                    'value' => $data['demo_tags_2'],
                ]
                );
            } else {
                $tags2->value = $data['demo_tags_2'];
                $tags2->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    public function ueditor() {
        $ueditor1 = (string)SystemDemo::where('name', 'demo_ueditor_1')->value('value');
        $ueditor2 = (string)SystemDemo::where('name', 'demo_ueditor_2')->value('value');

        return view('/admin/system/demo/ueditor', compact('ueditor1', 'ueditor2'));
    }

    public function ueditorSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            if (isset($data['demo_ueditor_1'])) {
                $ueditor1 = SystemDemo::where('name', 'demo_ueditor_1')->first();
                if (!$ueditor1) {
                    SystemDemo::create(['name'  => 'demo_ueditor_1',
                                        'value' => $data['demo_ueditor_1'],
                    ]
                    );
                } else {
                    $ueditor1->value = $data['demo_ueditor_1'];
                    $ueditor1->save();
                }
            }
            if (isset($data['demo_ueditor_2'])) {
                $ueditor2 = SystemDemo::where('name', 'demo_ueditor_2')->first();
                if (!$ueditor2) {
                    SystemDemo::create(['name'  => 'demo_ueditor_2',
                                        'value' => $data['demo_ueditor_2'],
                    ]
                    );
                } else {
                    $ueditor2->value = $data['demo_ueditor_2'];
                    $ueditor2->save();
                }
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    public function webuploaderImage() {
        $webuploader_image1 = (string)SystemDemo::where('name', 'demo_webuploader_image_1')->value('value');
        $webuploader_image2 = (string)SystemDemo::where('name', 'demo_webuploader_image_2')->value('value');

        return view('/admin/system/demo/webuploader-image', compact('webuploader_image1', 'webuploader_image2'));
    }

    public function webuploaderImageSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            $webuploader_image1 = SystemDemo::where('name', 'demo_webuploader_image_1')->first();
            if (!$webuploader_image1) {
                SystemDemo::create(['name'  => 'demo_webuploader_image_1',
                                    'value' => $data['demo_webuploader_image_1'],
                ]
                );
            } else {
                $webuploader_image1->value = $data['demo_webuploader_image_1'];
                $webuploader_image1->save();
            }
            $webuploader_image2 = SystemDemo::where('name', 'demo_webuploader_image_2')->first();
            if (!$webuploader_image2) {
                SystemDemo::create(['name'  => 'demo_webuploader_image_2',
                                    'value' => $data['demo_webuploader_image_2'],
                ]
                );
            } else {
                $webuploader_image2->value = $data['demo_webuploader_image_2'];
                $webuploader_image2->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    public function webuploaderFile() {
        $webuploader_file1 = (string)SystemDemo::where('name', 'demo_webuploader_file_1')->value('value');
        $webuploader_file2 = (string)SystemDemo::where('name', 'demo_webuploader_file_2')->value('value');

        return view('/admin/system/demo/webuploader-file', compact('webuploader_file1', 'webuploader_file2'));
    }

    public function webuploaderFileSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            $webuploader_file1 = SystemDemo::where('name', 'demo_webuploader_file_1')->first();
            if (!$webuploader_file1) {
                SystemDemo::create(['name'  => 'demo_webuploader_file_1',
                                    'value' => $data['demo_webuploader_file_1'],
                ]
                );
            } else {
                $webuploader_file1->value = $data['demo_webuploader_file_1'];
                $webuploader_file1->save();
            }
            $webuploader_file2 = SystemDemo::where('name', 'demo_webuploader_file_2')->first();
            if (!$webuploader_file2) {
                SystemDemo::create(['name'  => 'demo_webuploader_file_2',
                                    'value' => $data['demo_webuploader_file_2'],
                ]
                );
            } else {
                $webuploader_file2->value = $data['demo_webuploader_file_2'];
                $webuploader_file2->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    public function webuploaderImages() {
        $webuploader_images1 = (string)SystemDemo::where('name', 'demo_webuploader_images_1')->value('value');
        $webuploader_images2 = (string)SystemDemo::where('name', 'demo_webuploader_images_2')->value('value');

        return view('/admin/system/demo/webuploader-images', compact('webuploader_images1', 'webuploader_images2'));
    }

    public function webuploaderImagesSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            if (isset($data['demo_webuploader_images_1'])) {
                $data['demo_webuploader_images_1'] = implode(',', $data['demo_webuploader_images_1']);
            } else {
                $data['demo_webuploader_images_1'] = '';
            }
            $webuploader_images1 = SystemDemo::where('name', 'demo_webuploader_images_1')->first();
            if (!$webuploader_images1) {
                SystemDemo::create(['name'  => 'demo_webuploader_images_1',
                                    'value' => $data['demo_webuploader_images_1'],
                ]
                );
            } else {
                $webuploader_images1->value = $data['demo_webuploader_images_1'];
                $webuploader_images1->save();
            }
            if (isset($data['demo_webuploader_images_2'])) {
                $data['demo_webuploader_images_2'] = implode(',', $data['demo_webuploader_images_2']);
            } else {
                $data['demo_webuploader_images_2'] = '';
            }
            $webuploader_images2 = SystemDemo::where('name', 'demo_webuploader_images_2')->first();
            if (!$webuploader_images2) {
                SystemDemo::create(['name'  => 'demo_webuploader_images_2',
                                    'value' => $data['demo_webuploader_images_2'],
                ]
                );
            } else {
                $webuploader_images2->value = $data['demo_webuploader_images_2'];
                $webuploader_images2->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }

    public function webuploaderFiles() {
        $webuploader_files1 = (string)SystemDemo::where('name', 'demo_webuploader_files_1')->value('value');
        $webuploader_files2 = (string)SystemDemo::where('name', 'demo_webuploader_files_2')->value('value');

        return view('/admin/system/demo/webuploader-files', compact('webuploader_files1', 'webuploader_files2'));
    }

    public function webuploaderFilesSave(Request $request) {
        \DB::beginTransaction();//开启事务
        try {
            $data = $request->all();
            $data = ArrServer::null2strData($data);
            if (isset($data['demo_webuploader_files_1'])) {
                $data['demo_webuploader_files_1'] = implode(',', $data['demo_webuploader_files_1']);
            } else {
                $data['demo_webuploader_files_1'] = '';
            }
            $webuploader_files1 = SystemDemo::where('name', 'demo_webuploader_files_1')->first();
            if (!$webuploader_files1) {
                SystemDemo::create(['name'  => 'demo_webuploader_files_1',
                                    'value' => $data['demo_webuploader_files_1'],
                ]
                );
            } else {
                $webuploader_files1->value = $data['demo_webuploader_files_1'];
                $webuploader_files1->save();
            }
            if (isset($data['demo_webuploader_files_2'])) {
                $data['demo_webuploader_files_2'] = implode(',', $data['demo_webuploader_files_2']);
            } else {
                $data['demo_webuploader_files_2'] = '';
            }
            $webuploader_files2 = SystemDemo::where('name', 'demo_webuploader_files_2')->first();
            if (!$webuploader_files2) {
                SystemDemo::create(['name'  => 'demo_webuploader_files_2',
                                    'value' => $data['demo_webuploader_files_2'],
                ]
                );
            } else {
                $webuploader_files2->value = $data['demo_webuploader_files_2'];
                $webuploader_files2->save();
            }
            \DB::commit();//提交事务

            return $this->response('保存成功', 200);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), $e->getCode());
        }
    }
}
