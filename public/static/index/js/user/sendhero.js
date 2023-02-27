define(["jquery", "easy-admin"], function ($, ea) {

    // 初始配置
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'user.sendhero/log',
        copy_url: 'user.sendhero/copy',
        getConfigUrl: 'user.sendhero/getConfig',
        getConfigData: {
            ctype: 'heros'
        },
    };

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 控制器 发送道具
    var Controller = {

        index: function () {
            
            // 调用奖励逻辑
            let rew_vue = ea.api.formRewards({
                data: {
                    is_lan: 0,
                    rew_list: [],
                    rew_type: 'heros',
                    rew_fields: [
                        'heroid',
                    ],
                    config_heros: []
                },
                rew_elem: '#hero_num'
            });

            // 监听选择框（添加道具）
            form.on('select(add-rews)', function (obj) {

                if (obj.value == '' || obj.value == 0) return false;

                // 添加-奖励
                return rew_vue.add_rew(obj.value);
            });

            // 监听选择框（选择服务器）
            form.on('select(select-server)', function (obj) {

                let server = obj.value;
                if (!server) {
                    return false;
                }

                // 设置多语言显示状态
                let is_lan = $(obj.elem).find('option:selected').attr('is_lan');
                rew_vue.is_lan = (is_lan == 1) ? true : false;
            });

            // 监听按钮（日志）
            $('.list-btn').on('click', function (obj) {

                // 显示日志
                ea.open('日志', ea.url(init.index_url));
                return false;
            });

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_heros = data.config_heros;

                    // 初始化-奖励
                    let copy_rews = $('#copy_rews').val();
                    copy_rews ? rew_vue.init_rew(copy_rews) : rew_vue.add_rew($('#hero_num').val());

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            // 调用监听
            ea.listen(null, function (res) {

                // form提交成功返回
                ea.msg.success(res.msg, function () {

                    // 情况重要参数，以免重复发送！
                    $('#userpms').val('');
                });
            });
        },
        log: function () {
            
            // 获取服务器列表
            let admin_list    = eval('(' + $('#admin_list').val() + ')');
            let server_list   = eval('(' + $('#server_list').val() + ')');
            let config_heros  = eval('(' + $('#config_heros').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'server_id', minWidth: 150, title: '服务器<br/>server_id', search: 'select', selectList: server_list},
                    {field: 'admin_id', minWidth: 150, title: '管理员<br/>admin_id', search: 'select', selectList: admin_list},
                    {field: 'userpms', minWidth: 100, title: '用户参数<br/>userpms'},
                    {field: 'title', minWidth: 100, title: '标题<br/>title'},
                    {field: 'content', minWidth: 150, title: '内容<br/>content', search: false},
                    {field: 'rewards', width: 300, title: '奖励<br/>rewards', search: false, templet: ea.table.rewards, selectList: config_heros, rew_type: 'hero'},
                    {field: 'sender', minWidth: 150, title: '发送者<br/>sender'},
                    {field: 'expire_day', minWidth: 110, title: '过期天数<br/>expire_day', search: false},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 100,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                class: 'layui-btn layui-btn-xs',
                                method: 'get',
                                icon: '',
                                text: '复制',
                                title: '确认要复制这条邮件吗？',
                                auth: 'copy',
                                url: init.copy_url,
                                extend: 'data-close="true"'
                            }]
                        ]
                    }
                ]],
                toolbar: [ 'refresh' ]
            });

            ea.listen();
        },
    };

    return Controller;
});