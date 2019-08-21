<div class="modal fade" id="apps-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top">
        <div class="modal-content">
            <!-- Apps Block -->
            <div class="block block-themed block-transparent">
                <div class="block-header bg-primary-dark">
                    <ul class="block-options">
                        <li>
                            <button data-dismiss="modal" type="button"><i class="si si-close"></i></button>
                        </li>
                    </ul>
                    <h3 class="block-title">所有模块</h3>
                </div>
                <div class="block-content">
                    <div class="row text-center">
                        @foreach(\App\Servers\NavigationServer::modules() as $module)
                        <div class="col-xs-6 col-sm-3">
                            <a class="block block-rounded" href="{{$module['url']}}" target="_self">
                                <div class="block-content @if($module['id']==\App\Servers\NavigationServer::currentModuleId()) text-white bg-primary @else text-white bg-primary-dark @endif">
                                    <i class="{{$module['icon']}}"></i>
                                    <div class="font-w600 push-15-t push-15">{{$module['name']}}</div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- END Apps Block -->
        </div>
    </div>
</div>
