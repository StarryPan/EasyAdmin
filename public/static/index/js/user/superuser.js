define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                add_url: 'user.superuser/add',
                index_url: 'user.superuser/index',
                open_search: false,
            };

            // 获取管理员列表
            let admin_list  = eval('(' + $('#admin_list').val() + ')');

            // 获取服务器列表
            let server_list = eval('(' + $('#server_list').val() + ')');

            // 获取需求列表
            let demand_list = eval('(' + $('#demand_list').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'uid', width: 200, title: '用户ID<br/>uid'},
                    {field: 'content', title: '具体需求<br/>content', search: 'select', selectList: demand_list, selectDef: '全部需求', searchOp: '%*%'},
                    {field: 'admin_id', width: 200, title: '创建人<br/>admin_id', search: 'select', selectList: admin_list, selectDef: '全部管理员'},
                    {field: 'server_id', width: 200, title: '服务器<br/>server_id', search: 'select', selectList: server_list, selectDef: '全部服务器'},
                    {field: 'create_time', width: 200, title: '创建时间', search: 'range'},
                ]],
                toolbar: [ 
                    'refresh', 'add'
                ]
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
    };
    return Controller;
});