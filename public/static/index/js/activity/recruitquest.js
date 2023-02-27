define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    
    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'activity.recruitquest/index',
        add_url: 'activity.recruitquest/add',
        copy_url: 'activity.recruitquest/copy',
        edit_url: 'activity.recruitquest/edit',
        delete_url: 'activity.recruitquest/delete',
        modify_url: 'activity.recruitquest/modify',
        export_url: 'activity.recruitquest/export',
        import_url: 'activity.recruitquest/import',
        publish_url: 'activity.publish/index',
        getConfigUrl: 'user.senditem/getConfig',
        getConfigData: {
            ctype: 'items'
        },
    };

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 调用奖励逻辑
    let rew_vue = ea.api.formRewards({
        data: {
            info: {},
            rew_list: [],
            rew_type: 'items',
            rew_fields: [
                'itemid',
                'itemcnt',
            ],
            config_items: []
        },
    });

    // 监听选择框（添加道具）
    form.on('select(add-rews)', function (obj) {

        if (obj.value == '' || obj.value == 0) return false;

        // 添加-奖励
        return rew_vue.add_rew(obj.value);
    });

    
    /**
     * 控制类
     */
    var Controller = {

        index: function () {

            // 目标类型
            let goal_list    = eval('(' + $('#goal_list').val() + ')');

            // 限制列表
            let limit_list    = eval('(' + $('#limit_list').val() + ')');

            // 获取道具配置
            let config_items = eval('(' + $('#config_items').val() + ')');

            // 表头
            let cols = [
                {type: "checkbox"},
                {field: 'id', width: 80, title: 'ID<br/>ID'},
                {field: 'name', minWidth: 200, title: 'name<br/>名称', edit: 'text'},
                {field: 'sort', width: 80, title: 'sort<br/>排序', edit: 'text'},
                {field: 'desc', minWidth: 200, title: 'desc<br/>描述', edit: 'text'},
                {field: 'goal', minWidth: 200, title: 'goal<br/>目标类型', search: 'select', selectList: goal_list},
                {field: 'parm', minWidth: 100, title: 'parm<br/>目标参数', edit: 'text'},
                {field: 'value', minWidth: 100, title: 'value<br/>目标数值', edit: 'text'},
                {field: 'limit', minWidth: 100, title: 'limit<br/>开启天数', edit: 'text', search: 'select', selectList: limit_list},
                {field: 'before', minWidth: 100, title: 'before<br/>前置任务', edit: 'text'},
                {field: 'rewards', minWidth: 250, title: 'rewards<br/>任务奖励', search: false, templet: ea.table.rewards, selectList: config_items}, 
                {field: 'skip_to', minWidth: 100, title: 'skip_to<br/>跳转目标', edit: 'text'},
                {field: 'is_inherit', minWidth: 100, title: 'is_inherit<br/>继承状态'},
                {field: 'lan_name', minWidth: 100, title: 'lan_name<br/>多语言名称', search: false},
                {field: 'lan_desc', minWidth: 100, title: 'lan_desc<br/>多语言描述', search: false},
                {
                    width: 250,
                    title: '操作',
                    templet: ea.table.tool,
                    operat: [
                        'copy',
                        'edit',
                        'delete',
                    ]
                }
            ];

            ea.table.render({
                init: init,
                cols: [cols],
                toolbar: ['refresh', 'add', 'delete']
            });

            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen();
        },
        add: function () {
           
            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    // 初始化-奖励
                    rew_vue.add_rew($('#rew_num').val());

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            ea.listen();
        },
        edit: function () {

            // 等UI加载完毕后执行
            layer.ready(function () {

                let rewards  = $('#rewards').val();
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    rewards != null ? rew_vue.init_rew(rewards) : rew_vue.add_rew($('#rew_num').val());

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            ea.listen();
        },
        copy: function () {

            // 等UI加载完毕后执行
            layer.ready(function () {

                let rewards = $('#rewards').val();
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    rewards != null ? rew_vue.init_rew(rewards) : rew_vue.add_rew($('#rew_num').val());

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            ea.listen();
        },
        config: function () {

            // 表单赋值
            layui.$('.channel-search').on('click', function(){
                ea.msg.loading();
            });

            // 监听刷新
            $('body').on('click', '[data-refresh]', function () {

                window.location.reload();
            });

            setTimeout(function () {
                $('.layui-form-pane').fadeIn(500);
                $('.tr-loading').fadeOut(500, function() {
                    form.render();
                    $('.channel-config').fadeIn(500);
                });
            }, 50);

            
            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen(function(data) {
                return data;
            }, function(res) {
                ea.msg.success(res.msg, function () {
                    window.location.reload();
                });
            });
        },
    };
    return Controller;
});