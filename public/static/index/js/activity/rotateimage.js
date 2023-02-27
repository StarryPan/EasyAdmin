define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    
    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'activity.rotateimage/index',
        add_url: 'activity.rotateimage/add',
        copy_url: 'activity.rotateimage/copy',
        edit_url: 'activity.rotateimage/edit',
        delete_url: 'activity.rotateimage/delete',
        modify_url: 'activity.rotateimage/modify',
        export_url: 'activity.rotateimage/export',
        import_url: 'activity.rotateimage/import',
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

            let act_types       = eval('(' + $('#act_types').val() + ')');
            let rotate_types    = eval('(' + $('#rotate_types').val() + ')');

            // 表头
            let cols = [
                {type: "checkbox"},
                {field: 'id', width: 80, title: 'ID<br/>ID'},
                {field: 'act_type', width: 100, title: 'act_type<br/>活动', search: 'select', selectList: act_types},
                {field: 'name', minWidth: 200, title: 'name<br/>名称', edit: 'text'},
                {field: 'sort', width: 80, title: 'sort<br/>排序', edit: 'text'},
                {field: 'desc', minWidth: 200, title: 'desc<br/>描述', edit: 'text'},
                {field: 'type', minWidth: 100, title: 'type<br/>类型', search: 'select', selectList: rotate_types},
                {field: 'small_img', minWidth: 150, title: 'small_img<br/>轮播图片', edit: 'text'},
                {field: 'big_img', minWidth: 150, title: 'big_img<br/>背景图片', edit: 'text'},
                {field: 'tag_tex', minWidth: 150, title: 'tag_tex<br/>页签文字', edit: 'text'},
                {field: 'btn_tex', minWidth: 150, title: 'btn_tex<br/>按钮文字', edit: 'text'},
                {field: 'param1', minWidth: 100, title: 'param1<br/>额外参数', edit: 'text'},
                {field: 'act_id', minWidth: 100, title: 'act_id<br/>活动ID', edit: 'text'},
                {field: 'skip_to', minWidth: 100, title: 'skip_to<br/>跳转目标', edit: 'text'},
                {field: 'open_value', minWidth: 200, title: 'open_value<br/>时间限制', edit: 'text'},
                {
                    field: 'status',
                    width: 120,
                    title: '状态<br/>status',
                    templet: function (d) {
                        switch (d.status) {
                            case 0:
                                return '<div class="layui-badge-rim layui-bg-danger">关闭</div>';

                            case 1:
                                return '<div class="layui-badge-rim layui-bg-blue">正常</div>';

                            case 2:
                                return '<div class="layui-badge-rim layui-bg-green">轮播</div>';
                        
                            default:
                                return '<div class="layui-badge-rim layui-bg-warn">过期</div>';
                        }
                        
                    }
                },
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
                toolbar: ['refresh', 'add', 'delete', 'publish']
            });

            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen();
        },
        add: function () {
           
            // 等UI加载完毕后执行
            layer.ready(function () {
                // $('[name="end_time"]').val(ea.api.dateFormat(target_val.end_time));
                // $('[name="start_time"]').val(ea.api.dateFormat(target_val.start_time));
                ea.api.rangeDate({start_elem: '[name="start_time"]', end_elem: '[name="end_time"]'});// 范围时间组件
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