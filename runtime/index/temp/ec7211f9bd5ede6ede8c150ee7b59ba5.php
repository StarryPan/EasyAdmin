<?php /*a:2:{s:64:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\server\lists\add.html";i:1625542895;s:62:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\layout\default.html";i:1647844460;}*/ ?>
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
            <label class="layui-form-label required">ID</label>
            <div class="layui-input-block">
                <input type="text" name="id" class="layui-input" lay-verify="required" lay-reqtext="服务器唯一ID不能为空。" placeholder="请输入服务器ID" value="">
                <tip>请填写服务器唯一ID。</tip>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" class="layui-input" lay-verify="required" lay-reqtext="服务器名称不能为空。" placeholder="请输入服务器名称" value="">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">排序</label>
            <div class="layui-input-block">
                <input type="number" name="sort" lay-reqtext="服务器排序不能为空" placeholder="请输入服务器的排序" value="0" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">多语言</label>
            <div class="layui-input-block">
                <select name="is_lan">
                    <option value="0">关闭</option>
                    <option value="1">开启</option>
                </select>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">短连接</label>
            <div class="layui-input-block">
                <input type="text" name="shost" class="layui-input" lay-verify="required" lay-reqtext="服务器短连接地址不能为空。" placeholder="请输入短连接地址" value="">
                <tip>请填写服务器正确的短连接地址。</tip>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label required">长连接</label>
            <div class="layui-input-block">
                <input type="text" name="lhost" class="layui-input" lay-verify="required" lay-reqtext="服务器长连接地址不能为空。" placeholder="请输入长连接地址" value="">
                <tip>请填写服务器正确的长连接地址。</tip>
            </div>
        </div>

        <div class="hr-line"></div>
        <div class="layui-form-item text-center">
            <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>确认</button>
            <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
        </div>

    </form>
</div>
</body>
</html>