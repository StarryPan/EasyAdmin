<?php /*a:2:{s:61:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\index\welcome.html";i:1648783121;s:62:"C:\xampp\htdocs\YY\Bg\trunk\app\index\view\layout\default.html";i:1647844460;}*/ ?>
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
<link rel="stylesheet" href="/static/index/css/welcome.css?v=<?php echo time(); ?>" media="all">
<div class="layuimini-container">
    <div class="layuimini-main">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md8">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md6">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-warning icon"></i>数据统计</div>
                            <div class="layui-card-body">
                                <div class="welcome-module">
                                    <div class="layui-row layui-col-space10">

                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-blue">当前</span>
                                                        <h5>管理员数量</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins"><?php echo htmlentities($statistics['admin_count']); ?></h1>
                                                        <small>当前分类总记录数</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-green">当前</span>
                                                        <h5>服务器数量</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins"><?php echo htmlentities($statistics['server_count']); ?></h1>
                                                        <small>当前分类总记录数</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-cyan">当前</span>
                                                        <h5>商品数量</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins"><?php echo htmlentities($statistics['goods_count']); ?></h1>
                                                        <small>当前分类总记录数</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="layui-col-xs6">
                                            <div class="panel layui-bg-number">
                                                <div class="panel-body">
                                                    <div class="panel-title">
                                                        <span class="label pull-right layui-bg-orange">当前</span>
                                                        <h5>活动数量</h5>
                                                    </div>
                                                    <div class="panel-content">
                                                        <h1 class="no-margins"><?php echo htmlentities($statistics['activity_count']); ?></h1>
                                                        <small>当前分类总记录数</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-md6">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-credit-card icon icon-blue"></i>快捷入口</div>
                            <div class="layui-card-body">
                                <div class="welcome-module">
                                    <div class="layui-row layui-col-space10 layuimini-qiuck">

                                        <?php foreach($quicks as $vo): ?>
                                        <div class="layui-col-xs3 layuimini-qiuck-module">
                                            <a layuimini-content-href="<?php echo url($vo['href']); ?>" data-title="<?php echo htmlentities($vo['title']); ?>">
                                                <i class="<?php echo $vo['icon']; ?>"></i>
                                                <cite><?php echo htmlentities($vo['title']); ?></cite>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header"><i class="fa fa-line-chart icon"></i>报表统计</div>
                            <div class="layui-card-body">
                                <div id="echarts-records" style="width: 100%;min-height:500px"></div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>

            <div class="layui-col-md4">

                <div class="layui-card">
                    <div class="layui-card-header"><i class="fa fa-bullhorn icon icon-tip"></i>系统日志</div>
                    <div class="layui-card-body layui-text">
                        <?php foreach($log_list as $clog): ?>
                        <div class="layuimini-notice">
                            <div class="layuimini-notice-title"><?php echo !empty($clog['title']) ? htmlentities($clog['title']) : htmlentities($clog['url']); ?></div>
                            <div class="layuimini-notice-extra"><?php echo htmlentities(date('Y-m-d H:i',!is_numeric($clog['create_time'])? strtotime($clog['create_time']) : $clog['create_time'])); ?></div>
                            <div class="layuimini-notice-content layui-hide">
                                <?php foreach($clog as $ckey => $cval): ?>
                                    <?php echo htmlentities($ckey); ?> : <?php echo htmlentities($cval); ?> <br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- <div class="layui-card">
                    <div class="layui-card-header"><i class="fa fa-fire icon"></i>下载信息</div>
                    <div class="layui-card-body layui-text">
                        <table class="layui-table">
                            <colgroup>
                                <col width="100">
                                <col>
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>框架名称</td>
                                    <td>
                                        EasyAdmin
                                    </td>
                                </tr>
                                <tr>
                                    <td>当前版本</td>
                                    <td>v2.0.0</td>
                                </tr>
                                <tr>
                                    <td>主要特色</td>
                                    <td>零门槛 / 响应式 / 清爽 / 极简</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div> -->


            </div>
        </div>
    </div>
</div>
</body>
</html>