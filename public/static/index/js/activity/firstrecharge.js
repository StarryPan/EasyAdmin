define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    
    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'activity.firstrecharge/index',
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

            // 监听刷新
            $('body').on('click', '[data-refresh]', function () {

                window.location.reload();
            });

            // 等UI加载完毕后执行
            layer.ready(function () {

                let rewards = $('#rewards').val();
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    rewards != '' ? rew_vue.init_rew(rewards) : rew_vue.add_rew($('#rew_num').val());

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

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