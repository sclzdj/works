<aside id="side-overlay">
    <!-- Side Overlay Scroll Container -->
    <div id="side-overlay-scroll">
        <!-- Side Header -->
        <div class="side-header side-content">
            <!-- Layout API, functionality initialized in App() -> uiLayoutApi() -->
            <button class="btn btn-default pull-right" type="button" data-toggle="layout" data-action="side_overlay_close">
                <i class="fa fa-times"></i>
            </button>
            <span>
                    <img class="img-avatar img-avatar32" src="@if(auth('admin')->user()->avatar!=='') {{auth('admin')->user()->avatar}} @else {{asset('/static/admin/img/avatar.jpg').'?'.$SFV}} @endif" alt="用户头像">
                    <span class="font-w600 push-10-l">{{auth('admin')->user()->username}}</span>
                </span>
        </div>
        <!-- END Side Header -->
        <!--侧栏-->
        <!-- Side Content -->
        <div class="side-content remove-padding-t" id="aside">
            <!-- Side Overlay Tabs -->
            <div class="block pull-r-l border-t">
                <div class="block-content">
                    @if(\App\Servers\PermissionServer::allowActionOne('Admin\System\IndexController@config'))
                        <div class="block pull-r-l">
                            <div class="block-header bg-gray-lighter">
                                <ul class="block-options">
                                    <li>
                                        <button type="button" data-toggle="block-option" data-action="content_toggle"></button>
                                    </li>
                                </ul>
                                <h3 class="block-title">快捷设置</h3>
                            </div>
                            <div class="block-content">
                                <div class="form-bordered">
                                    @foreach(\App\Model\Admin\SystemConfig::where('type','basic')->where('genre','switch')->get() as $systemConfig)
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-xs-8">
                                                    <div class="font-s13 font-w600">{{$systemConfig['title']}}</div>
                                                    <div class="font-s13 font-w400 text-muted">{{$systemConfig['tips']}}</div>
                                                </div>
                                                <div class="col-xs-4 text-right">
                                                    <label class="css-input switch switch-sm switch-primary push-10-t">
                                                        <input class="side-switch" type="checkbox" name="{{$systemConfig['name']}}" value="1" @if($systemConfig['value']) checked @endif><span></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <!-- END Side Overlay Tabs -->
        </div>
        <!-- END Side Content -->
    </div>
    <!-- END Side Overlay Scroll Container -->
</aside>
