define(["jquery", "easy-admin"], function ($, ea) {

    // 初始配置
    let init  = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'user.sendnotice/log',
    };

    // Layui 组件
    let layer = layui.layer;

    // 控制器 发送道具
    var Controller = {

        index: function () {

            // 监听按钮（日志）
            $('.list-btn').on('click', function (obj) {

                // 显示日志
                ea.open('日志', ea.url(init.index_url));
                return false;
            });

            // 调用监听
            ea.listen(null, function (res) {

                // form提交成功返回
                ea.msg.success(res.msg, function () {

                    // 情况重要参数，以免重复发送！
                    $('#userpms').val('');
                });
            });

            // 等UI加载完毕后执行
            layer.ready(function () {
                // 显示主内容
                $('.layuimini-container').show();
            });
        },
        log: function () {
            
            // 获取服务器列表
            let admin_list    = eval('(' + $('#admin_list').val() + ')');
            let server_list   = eval('(' + $('#server_list').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'server_id', minWidth: 150, title: '服务器<br/>server_id', search: 'select', selectList: server_list},
                    {field: 'admin_id', minWidth: 150, title: '管理员<br/>admin_id', search: 'select', selectList: admin_list},
                    {field: 'userpms', minWidth: 100, title: '用户参数<br/>userpms'},
                    {field: 'title', title: '标题<br/>title'},
                    {field: 'content', title: '内容<br/>content', search: false},
                    {field: 'sender', minWidth: 150, title: '发送者<br/>sender'},
                    {field: 'expire_day', minWidth: 110, title: '过期天数<br/>expire_day', search: false},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                ]],
                toolbar: [ 'refresh' ]
            });

            ea.listen();
        },
    };

    return Controller;
});