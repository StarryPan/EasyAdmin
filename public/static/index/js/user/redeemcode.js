define(["jquery", "easy-admin"], function ($, ea, vue) {

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 初始化
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'user.redeemcode/index',
        add_url: 'user.redeemcode/add',
        edit_url: 'user.redeemcode/edit',
        delete_url: 'user.redeemcode/delete',
        export_url: 'user.redeemcode/export',
        import_url: 'user.redeemcode/import',
        getConfigUrl: 'user.redeemcode/getConfig',
        getConfigData: {
            ctype: 'items'
        },
    };

    // 时间字段
    let timeFields = {
        end: '[name="end_time"]',
        start: '[name="start_time"]',
    };

    var Controller = {

        index: function () {

            // 获取服务器列表
            let admin_list   = eval('(' + $('#admin_list').val() + ')');
            let config_items = eval('(' + $('#config_items').val() + ')');
            let channel_list = eval('(' + $('#channel_list').val() + ')');
            let redeem_types = eval('(' + $('#redeem_types').val() + ')');
            channel_list[''] = '全部渠道';

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 100, title: 'ID<br/>id'},
                    {field: 'code', minWidth: 200, title: '兑换码<br/>code'},
                    {field: 'channel_key', width: 200, title: '渠道<br/>channel_key', search: 'select', selectList: channel_list},
                    {field: 'admin_id', width: 200, title: '管理员<br/>admin_id', search: 'select', selectList: admin_list},
                    {field: 'redeem_type', width: 120, title: '兑换类型<br/>redeem_type', search: 'select', selectList: redeem_types},
                    {field: 'batch', width: 100, title: '创建批号<br/>batch'},
                    {field: 'rewards', minWidth: 240, title: '兑换奖励<br/>rewards', search: false, templet: ea.table.rewards, selectList: config_items},
                    {field: 'descr', width: 100, title: '备注<br/>descr', search: false},
                    {
                        field: 'redeem_time',
                        width: 220,
                        title: '兑换时间<br/>redeem_time',
                        search: false,
                        templet: function (d) {
                            let span_str = '不限制';

                            if (d.start_time != 0 && d.end_time != 0) {

                                span_str  = '';
                                span_str += '开始：' + ea.api.dateFormat(d.start_time) + '<br/>';
                                span_str += '结束：' + ea.api.dateFormat(d.end_time);
                            }

                            return span_str;
                        }
                    },
                    {
                        field: 'use_num',
                        width: 150,
                        title: '兑换状态<br/>use_num',
                        search: false,
                        templet: function (d) {
                            let use_num  = d.use_num;
                            let span_str = '<span class="layui-badge-rim layui-bg-green">待兑换</span>';

                            if (use_num == 1 && d.redeem_type == 1) {

                                span_str = '<span class="layui-badge-rim layui-bg-primary">已兑换</span>';
                            }else if (use_num > 0) {

                                span_str = '<span class="layui-badge-rim layui-bg-warm">兑换人数 '+use_num+'</span>';

                            }else {

                                if (d.start_time != 0 && d.end_time != 0) {

                                    // 获取当前时间
                                    let now_time = ea.api.dateTime();
                                    if (d.start_time > now_time) {

                                        span_str = '<span class="layui-badge-rim layui-bg-blue">未开始</span>';
                                    }else if (d.end_time < now_time) {

                                        span_str = '<span class="layui-badge-rim layui-bg-red">已过期</span>';
                                    }else {

                                        span_str = '<span class="layui-badge-rim layui-bg-green">待兑换</span>';
                                    }
                                }
                            }

                            return span_str;
                        }
                    },
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 120,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            'delete'
                        ]
                    }
                ]],
            });
            
            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen();
        },
        add: function () {

            // 调用奖励逻辑
            let rew_vue = ea.api.formRewards({
                data: {
                    is_lan: 0,
                    rew_list: [],
                    rew_type: 'items',
                    rew_fields: [
                        'itemid',
                        'itemcnt',
                    ],
                    config_items: []
                }
            });

            // 监听选择框（添加道具）
            form.on('select(add-rews)', function (obj) {

                if (obj.value == '' || obj.value == 0) return false;

                // 添加-奖励
                return rew_vue.add_rew(obj.value);
            });

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

            ea.api.rangeDate({end_elem: '[name="end_time"]', start_elem: '[name="start_time"]'});
            ea.listen();
        },
        edit: function () {

            // 调用奖励逻辑
            let rew_vue = ea.api.formRewards({
                data: {
                    is_lan: 0,
                    rew_list: [],
                    rew_type: 'items',
                    rew_fields: [
                        'itemid',
                        'itemcnt',
                    ],
                    config_items: []
                }
            });

            // 监听选择框（添加道具）
            form.on('select(add-rews)', function (obj) {

                if (obj.value == '' || obj.value == 0) return false;

                // 添加-奖励
                return rew_vue.add_rew(obj.value);
            });

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    // 初始化-奖励
                    let rew_json = $('#rew_json').val();
                    rew_json ? rew_vue.init_rew(rew_json) : rew_vue.add_rew($('#rew_num').val());

                    // 时间戳转日期格式
                    $(timeFields.end).val(ea.api.dateFormat($(timeFields.end).val()));
                    $(timeFields.start).val(ea.api.dateFormat($(timeFields.start).val()));

                    // 显示主内容
                    $('.layuimini-container').show();

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            ea.api.rangeDate({end_elem: timeFields.end, start_elem: timeFields.start});
            ea.listen();
        },
        log: function () {

            // 初始配置
            let init  = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'user.redeemcode/log',
                copy_url: 'user.redeemcode/copy',
                getConfigUrl: 'user.redeemcode/getConfig',
                getConfigData: {
                    ctype: 'items'
                },
            };
            
            // 获取服务器列表
            let server_list  = eval('(' + $('#server_list').val() + ')');
            let config_items = eval('(' + $('#config_items').val() + ')');
            let channel_list = eval('(' + $('#channel_list').val() + ')');
            channel_list[''] = '全部渠道';

            ea.table.render({
                init: init,
                cols: [[
                    {field: 'id', width: 100, title: 'ID<br/>id'},
                    {field: 'code', minWidth: 200, title: '兑换码<br/>code'},
                    {field: 'uid', minWidth: 200, title: '用户ID<br/>uid'},
                    {field: 'channel_key', width: 200, title: '渠道<br/>channel_key', search: 'select', selectList: channel_list},
                    {field: 'server_id', minWidth: 220, title: '服务器<br/>server_id', search: 'select', selectList: server_list},
                    {field: 'user_ip', minWidth: 200, title: '用户IP地址<br/>user_ip'},
                    {field: 'batch', width: 100, title: '创建批号<br/>batch'},
                    {field: 'rcode_id', width: 100, title: '兑换ID<br/>rcode_id'},
                    {field: 'rewards', minWidth: 240, title: '兑换奖励<br/>rewards', search: false, templet: ea.table.rewards, selectList: config_items},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'}
                ]],
            });

            ea.listen();
        },
    };

    return Controller;
});