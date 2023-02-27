define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    
    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'activity.registersign/index',
        add_url: 'activity.registersign/add',
        edit_url: 'activity.registersign/edit',
        delete_url: 'activity.registersign/delete',
        modify_url: 'activity.registersign/modify',
        export_url: 'activity.registersign/export',
        import_url: 'activity.registersign/import',
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
            rew_type: 'sign',
            rew_fields: [
                'itemid',
                'itemcnt',
            ],
            rew_default: {
                'itemid': 1,
                'itemcnt': 100,
            },
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

            // 表头
            let cols = [
                {type: "checkbox"},
                {field: 'id', width: 80, title: '自增ID<br/>ID'},
                {field: 'name', width: 200, title: '名称<br/>name'},
            ];

            // 获取道具配置
            let config_items  = eval('(' + $('#config_items').val() + ')');
            
            // 变量天数
            for (let day = 1; day <= 7; day++) {
                
                let field = 'day' + day;
                cols.push({field: field, minWidth: 200, title: field + '<br/>第'+day+'天', search: false, templet: ea.table.rewards, selectList: config_items});
            }

            cols.push({
                width: 250,
                title: '操作',
                templet: ea.table.tool,
                operat: [
                    'edit',
                    'delete'
                ]
            });

            ea.table.render({
                init: init,
                cols: [cols],
                toolbar: [ 'refresh', 'add', 'delete', 'publish' ]
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

            let row  = $('#row_info').val();
            let info = $.parseJSON(row);

            rew_vue.info = info;

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    // 初始化-奖励
                    let edit_rews = [];

                    for (let day = 1; day <= 31; day++) {
                        
                        let nkey = 'day' + day;

                        if (info[nkey] != undefined) {

                            edit_rews.push(info[nkey]);
                        }
                    }

                    edit_rews != null ? rew_vue.init_rew(edit_rews) : rew_vue.add_rew($('#rew_num').val());

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