<?php /*a:2:{s:69:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\system\auth\authorize.html";i:1631084532;s:62:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\layout\default.html";i:1647844460;}*/ ?>
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
    <form id="app-form" class="layui-form layuimini-form">

        <div class="layui-form-item">
            <label class="layui-form-label">最高权限</label>
            <div class="layui-input-block">
                <input type="checkbox" id="all_unlock" lay-text="开启|停用" lay-skin="switch" lay-filter="all_checkbox_switch">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">权限名称</label>
            <div class="layui-input-block">
                <input type="text" name="title" readonly class="layui-input" value="<?php echo htmlentities((isset($row['title']) && ($row['title'] !== '')?$row['title']:'')); ?>">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">分配服务器</label>
            <div class="layui-input-block">
                <div id="server_ids" class="demo-tree-more"></div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">分配节点</label>
            <div class="layui-input-block">
                <div id="node_ids" class="demo-tree-more"></div>
            </div>
        </div>

        <input type="hidden" name="id" readonly class="layui-input" value="<?php echo htmlentities($row['id']); ?>">

        <div class="hr-line"></div>
        <div class="layui-form-item text-center">
            <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit="system.auth/saveAuthorize">确认</button>
            <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
        </div>

    </form>
</div>
</body>
</html>