<footer id="page-footer" class="content-mini content-mini-full font-s12 bg-gray-lighter clearfix">
    <div class="pull-right">
        @php
            $admin_copyright=\App\Model\Admin\SystemConfig::getVal('admin_copyright','admin');
        @endphp
        Crafted with <i class="fa fa-heart text-city"></i> by <a class="font-w600" href="{{\App\Servers\NavigationServer::homeUrl()}}" target="_blank">{{($admin_copyright!==''?$admin_copyright:'行风工作室')}}</a>
    </div>
    <div class="pull-left">
        @php
            $admin_icp=\App\Model\Admin\SystemConfig::getVal('admin_icp','admin');
        @endphp
        <a class="font-w600" href="http://www.beianbeian.com/" target="_blank">{{($admin_icp!==''?$admin_icp:'Programmer Inn @ sclzdj')}}</a>
    </div>
</footer>
