<?php /*a:5:{s:64:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\system\log\index.html";i:1628245575;s:62:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\layout\default.html";i:1647844460;s:68:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\system\log\systemlog.html";i:1625542895;s:66:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\system\log\curllog.html";i:1625542895;s:67:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\system\log\errorlog.html";i:1625542895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo sysconfig('site','site_name'); ?></title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link rel="stylesheet" href="/static/index/css/public.css?v=<?php echo htmlentities($version); ?>" media="all">
    <script>
        window.CONFIG = {
            ADMIN: "<?php echo htmlentities((isset($adminModuleName) && ($adminModuleName !== '')?$adminModuleName:'admin')); ?>",
            CONTROLLER_JS_PATH: "<?php echo htmlentities((isset($thisControllerJsPath) && ($thisControllerJsPath !== '')?$thisControllerJsPath:'')); ?>",
            ACTION: "<?php echo htmlentities((isset($thisAction) && ($thisAction !== '')?$thisAction:'')); ?>",
            AUTOLOAD_JS: "<?php echo htmlentities((isset($autoloadJs) && ($autoloadJs !== '')?$autoloadJs:'false')); ?>",
            IS_SUPER_ADMIN: "<?php echo htmlentities((isset($isSuperAdmin) && ($isSuperAdmin !== '')?$isSuperAdmin:'false')); ?>",
            VERSION: "<?php echo htmlentities((isset($version) && ($version !== '')?$version:'1.0.0')); ?>",
        };
    </script>
    <script src="/static/plugs/layui-v2.5.6/layui.all.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
    <script src="/static/plugs/require-2.3.6/require.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
    <script src="/static/config-admin.js?v=<?php echo htmlentities($version); ?>" charset="utf-8"></script>
</head>
<body>
<div class="layuimini-container">
    <div class="layuimini-main" id="app">

        <!-- 管理员列表 -->
        <input type="hidden" id="admin_list" value="<?php echo htmlentities($admin_list); ?>">

        <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
            <ul class="layui-tab-title">
                <li class="layui-this" tab-table="systemlog">系统日志</li>
                <li tab-table="curllog">请求日志</li>
                <li tab-table="errorlog">报错日志</li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <div class="layuimini-container">
    <div class="layuimini-main">
        <table id="currentTable_systemlog" class="layui-table layui-hide"
               lay-filter="currentTable_systemlog">
        </table>
    </div>
</div>
                </div>
                <div class="layui-tab-item">
                    <div class="layuimini-container">
    <div class="layuimini-main">
        <table id="currentTable_curllog" class="layui-table layui-hide"
               lay-filter="currentTable_curllog">
        </table>
    </div>
</div>
                </div>
                <div class="layui-tab-item">
                    <div class="layuimini-container">
    <div class="layuimini-main">
        <table id="currentTable_errorlog" class="layui-table layui-hide"
               lay-filter="currentTable_errorlog">
        </table>
    </div>
</div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>