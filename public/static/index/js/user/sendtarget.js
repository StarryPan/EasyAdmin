define(["jquery", "easy-admin"], function ($, ea) {

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    /**
     * 控制器 - 发送目标邮件
     */
    var Controller = {

        index: function () {

            let init = {
                index_url: 'user.sendtarget/index',
                list_url: 'user.sendtarget/list',
                getConfigUrl: 'user.sendtarget/getConfig',
                getConfigData: {
                    ctype: 'items'
                },
            };

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
                    target_type: 0,
                    config_items: []
                }
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

            // 更改目标类型
            let changeTargetType = function (target_type = 0) {
              
                // 设置目标显示状态
                rew_vue.target_type = Number(target_type || 0);

                // 类型组件调用
                switch (rew_vue.target_type) {
                    case 3:// 登陆时间
                    case 4:// 注册时间
                        var elem_v = {start_elem: '[name="login_start"]', end_elem: '[name="login_end"]'};

                        if (rew_vue.target_type == 4) {

                            elem_v.start_elem = '[name="reg_start"]', elem_v.end_elem = '[name="reg_end"]';
                        }
                        
                        setTimeout(function () {
                            let start_str = $(elem_v.start_elem).val();
                            if (ea.api.isJSON(start_str)) {
                                let target_val = JSON.parse(start_str);
                                $(elem_v.end_elem).val(ea.api.dateFormat(target_val.end_time));
                                $(elem_v.start_elem).val(ea.api.dateFormat(target_val.start_time));
                            }
                            ea.api.rangeDate(elem_v);// 范围时间组件
                        }, 50);
                    break;

                    default:break;
                }

                return true;
            };

            // 监听选择框（选择目标类型）
            form.on('select(select-target)', function (obj) {

                let target_type = obj.value;
                changeTargetType(target_type);
            });

            // 监听按钮（列表）
            $('.target-list-btn').on('click', function (obj) {

                // 验证指定的字段
                let field_vals = ea.api.fieldRequiredVerify('server_id');

                if (field_vals) {

                    let list_url = init.list_url;
                    list_url    += '?server_id=' + field_vals.server_id;

                    // 显示列表
                    ea.open('列表', ea.url(list_url));
                };

                return false;
            });

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    rew_vue.config_items = data.config_items;

                    // 更改目标类型
                    let target_type = $('[name="target_type"]').val();
                    target_type ? changeTargetType(target_type) : '';

                    // 初始化-奖励
                    let copy_rews = $('#copy_rews').val();
                    copy_rews ? rew_vue.init_rew(copy_rews) : rew_vue.add_rew($('#rew_num').val());

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
                    $('[name="server_id"]').val('');
                    form.render();
                });
            });
        },
        list: function () {

            let init = {
                copy_url: 'user.sendtarget/copy',
                index_url: 'user.sendtarget/list',
                delete_url: 'user.sendtarget/list_delete',
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
            };

            // 服务器ID
            let server_id    = '?server_id=' + $('#server_id').val();

            // 拼接url把服务器ID传进去
            init.index_url  += server_id;
            init.delete_url += server_id;

            // 获取服务器列表
            let mail_types   = eval('(' + $('#mail_types').val() + ')');
            let target_types = eval('(' + $('#target_types').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 100, title: 'ID<br/>id'},
                    {field: 'mtype', width: 100, title: '类型<br/>mtype', search: 'select', selectList: mail_types},
                    {field: 'target_type', width: 120, title: '目标类型<br/>target_type', search: 'select', selectList: target_types},
                    {field: 'target', minWidth: 80, title: '目标<br/>target', search: false, templet: function (d) {
                        let target_str = '';
                        switch (d.target_type) {
                            case 3:// '指定登陆时间'
                            case 4:// '指定注册时间'
                                let jdata  = JSON.parse(d.target);
                                target_str = ea.api.dateFormat(jdata.start_time) + ' ~ ' + ea.api.dateFormat(jdata.end_time);
                            break;
                        
                            default:
                                target_str = d.target;
                                break;
                        }

                        return target_str;
                    }},
                    {field: 'title', minWidth: 80, title: '标题<br/>title'},
                    {field: 'content', minWidth: 80, title: '内容<br/>content', search: false},
                    {field: 'rewards', minWidth: 80, title: '奖励<br/>rewards', search: false},
                    {field: 'sender', width: 150, title: '发送者<br/>sender'},
                    {field: 'expire_day', width: 150, title: '过期天数<br/>expire_day', search: false},
                    {field: 'create_time', minWidth: 80, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 150,
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
                            },
                            {
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                                method: 'get',
                                icon: '',
                                text: '删除',
                                title: '确定删除？',
                                auth: 'delete',
                                url: init.delete_url,
                                extend: ""
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