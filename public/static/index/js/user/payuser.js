define(["jquery", "easy-admin"], function ($, ea) {

    // 初始配置
    let init  = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        copy_url: 'user.payuser/copy',
        index_url: 'user.payuser/log',
        getConfigUrl: 'user.payuser/getConfig',
        getConfigData: {
            ctype: 'items'
        },
    };

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 控制器 发送道具
    var Controller = {

        index: function () {

            // 监听按钮（日志）
            $('.list-btn').on('click', function (obj) {

                // 显示日志
                ea.open('日志', ea.url(init.index_url));
                return false;
            });

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 显示主内容
                $('.layuimini-container').show();
            });

            // 刷新验证码
            let refresh_captcha = function() {
                $('#clean_captcha').val('');
                $('#refreshCaptcha').trigger('click');
            };

            // 调用监听
            ea.listen(null, function (res) {

                // form提交成功返回
                ea.msg.success(res.msg, function () {

                    // 情况重要参数，以免重复发送！
                    $('#userpms').val('');
                    refresh_captcha();// 刷新验证码
                });
            }, function (err) {
                ea.msg.alert(err.msg, function () {
                    refresh_captcha();// 刷新验证码
                });
            });
        },
        log: function () {
            
            // 获取服务器列表
            let admin_list   = eval('(' + $('#admin_list').val() + ')');
            let server_list  = eval('(' + $('#server_list').val() + ')');
            let detail_list  = eval('(' + $('#detail_list').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'server_id', minWidth: 150, title: '服务器<br/>server_id', search: 'select', selectList: server_list},
                    {field: 'admin_id', minWidth: 150, title: '管理员<br/>admin_id', search: 'select', selectList: admin_list},
                    {field: 'userpms', minWidth: 100, title: '用户参数<br/>userpms'},
                    {field: 'detail_id', minWidth: 150, title: '补单商品<br/>detail_id', search: 'select', selectList: detail_list},
                    {field: 'order_id', minWidth: 150, title: '订单号<br/>order_id'},
                    {
                        field: 'state',
                        width: 120,
                        title: '补单状态<br/>state',
                        search: false,
                        templet: function (d) {
                            let rs_data  = eval(d.rs_data);
                            let span_str = '<span class="layui-badge-rim layui-bg-red">失败</span>';

                            if (rs_data.msg == null) {

                                span_str = '<span class="layui-badge-rim layui-bg-primary">未知状态</span>';
                            }else {

                                if (rs_data.code == 0) {

                                    span_str = '<span class="layui-badge-rim layui-bg-blue">成功</span>';
                                }
                            }

                            return span_str;
                        }
                    },
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
                                title: '确认要复制这条日志吗？',
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