define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    
    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
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
            monthly_list: [],
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

            // 初始化地址
            init.add_url    = 'activity.totalrecharge/add';
            init.copy_url   = 'activity.totalrecharge/copy';
            init.edit_url   = 'activity.totalrecharge/edit';
            init.index_url  = 'activity.totalrecharge/index';
            init.delete_url = 'activity.totalrecharge/delete';
            init.modify_url = 'activity.totalrecharge/modify';

            // 获取开启类型
            let open_types = eval('(' + $('#open_types').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'sort', width: 80, title: 'sort<br/>排序', edit: 'text'},
                    {field: 'title', minWidth: 120, title: '标题<br/>title', edit: 'text'},
                    {field: 'descr', minWidth: 120, title: '描述<br/>descr', edit: 'text'},
                    {field: 'status', minWidth: 80, title: '状态<br/>status', search: 'select', selectList: {0: '关闭', 1: '正常'}, templet: ea.table.switch},
                    {field: 'open_type', width: 100, title: '开启类型<br/>open_type', search: 'select', selectList: open_types},
                    {field: 'open_value', minWidth: 80, title: '开启数值<br/>open_value', edit: 'text'},
                    {field: 'update_time', width: 200, title: '更新时间<br/>update_time', search: 'range'},
                    {field: 'create_time', width: 200, title: '创角时间<br/>create_time', search: 'range'},
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
                ]],
                toolbar: ['refresh', 'add', 'delete', 'publish'],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        copy: function () {
            ea.listen();
        },
        quest: function () {

            // 初始化地址
            init.add_url     = 'activity.totalrecharge/addquest';
            init.copy_url    = 'activity.totalrecharge/copyquest';
            init.edit_url    = 'activity.totalrecharge/editquest';
            init.index_url   = 'activity.totalrecharge/quest';
            init.delete_url  = 'activity.totalrecharge/deletequest';
            init.modify_url  = 'activity.totalrecharge/modifyquest';
            init.export_url  = 'activity.totalrecharge/export',
            init.import_url  = 'activity.totalrecharge/import',
            init.open_search =  true;

            // 目标类型
            let goal_list    = eval('(' + $('#goal_list').val() + ')');

            // 限制列表
            let limit_list    = eval('(' + $('#limit_list').val() + ')');

            // 获取道具配置
            let config_items  = eval('(' + $('#config_items').val() + ')');

            // 活动列表
            let activity_list = eval('(' + $('#activity_list').val() + ')');

            // 表头
            let cols = [
                {type: "checkbox"},
                {field: 'act_id', title: '活动', hide: true, search: 'select', selectList: activity_list, selectDef: '请选择活动'},
                {field: 'id', width: 80, title: 'ID<br/>ID'},
                {field: 'name', minWidth: 200, title: 'name<br/>名称', edit: 'text'},
                {field: 'sort', width: 80, title: 'sort<br/>排序', edit: 'text'},
                {field: 'desc', minWidth: 200, title: 'desc<br/>描述', edit: 'text'},
                {field: 'goal', minWidth: 200, title: 'goal<br/>目标类型', search: 'select', selectList: goal_list},
                {field: 'parm', minWidth: 100, title: 'parm<br/>目标参数', edit: 'text'},
                {field: 'value', minWidth: 100, title: 'value<br/>目标数值', edit: 'text'},
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
        addquest: function () {
           
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
        editquest: function () {

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
        copyquest: function () {

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
    };
    return Controller;
});