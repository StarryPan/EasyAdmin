<?php /*a:2:{s:68:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\server\refresh\index.html";i:1655706833;s:62:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\layout\default.html";i:1647844460;}*/ ?>
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
    <div class="layuimini-main">
        <form id="app-form" class="layui-form layuimini-form" action="javascript:;">

            <div class="layui-form-item layui-row layui-col-xs12">
                <label class="layui-form-label required">服务器</label>
                <div class="layui-input-block">
                    <select id="server_id" name="server_id" lay-verify="required" lay-reqtext="请选择服务器。" lay-search>
                        <option value="">请选择服务器</option>
                        <?php foreach($serverList as $vo): ?>
                            <option value="<?php echo htmlentities($vo['id']); ?>"><?php echo htmlentities($vo['id']); ?>. <?php echo $vo['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="layui-form-item text-center">
                <button class="layui-btn layui-btn-sm" style="background-color: #009688;" ref-anim="2" ref-type="refreshConfig">
                    刷新配置缓存
                </button>

                <button class="layui-btn layui-btn-sm" style="background-color: #ffb800;" ref-anim="2" ref-type="refreshLuaHelper">
                    刷新 Lua 缓存
                </button>

                <button class="layui-btn layui-btn-sm" style="background-color: #1E9FFF;" ref-anim="6" ref-type="refreshTableStructure">
                    刷新数据表结构
                </button>

                <button class="layui-btn layui-btn-sm" style="background-color: #70628e;" ref-anim="1" ref-type="refreshActivityRedis">
                    刷新活动缓存
                </button>

                <button class="layui-btn layui-btn-sm" style="background-color: #1636a5;" ref-anim="4" ref-type="updateServerNewest">
                    更新服务器到最新
                </button>
                
                <button class="layui-btn layui-btn-sm" style="background-color: #5fb878;" ref-anim="4" ref-type="refreshAsynPvpRobot">
                    刷新异步pvp机器人
                </button>
            </div>

        </form>
    </div>
</div>
</body>
</html>