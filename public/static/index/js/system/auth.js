define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.auth/index',
        add_url: 'system.auth/add',
        edit_url: 'system.auth/edit',
        delete_url: 'system.auth/delete',
        export_url: 'system.auth/export',
        modify_url: 'system.auth/modify',
        authorize_url: 'system.auth/authorize',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'title', minWidth: 80, title: '权限名称'},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {field: 'status', title: '状态', width: 100, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            [{
                                text: '授权',
                                url: init.authorize_url,
                                method: 'open',
                                auth: 'authorize',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        authorize: function () {
            var tree = layui.tree;
            var form = layui.form;

            ea.request.get(
                {
                    url: window.location.href,
                }, function (res) {

                    // 节点列表
                    let node_list = res.data.node_list || [];
                    tree.render({
                        elem: '#node_ids',
                        data: node_list,
                        showCheckbox: true,
                        id: 'nodeDataId',
                    });

                    // 服务器列表
                    let server_list = res.data.server_list || [];
                    tree.render({
                        elem: '#server_ids',
                        data: server_list,
                        showCheckbox: true,
                        id: 'serverDataId',
                    });

                    // 检查是否全部选中
                    if (res.data.is_all) {
                        $("input[type='checkbox']").prop("checked", true);
                        form.render('checkbox');
                    }
                }
            );

            // 监听开关
            form.on('switch(all_checkbox_switch)', function(data){

                if ( this.checked ) {

                    $("input[type='checkbox']").prop("checked", true);
                }else{

                    $("input[type='checkbox']").prop("checked", false);
                }

                form.render('checkbox');
            });

            ea.listen(function (data) {

                // 获取节点数据
                var node_ids = [];
                var checkedData = tree.getChecked('nodeDataId');
                $.each(checkedData, function (i, v) {
                    node_ids.push(v.id);
                    if (v.children !== undefined && v.children.length > 0) {
                        $.each(v.children, function (ii, vv) {
                            node_ids.push(vv.id);
                        });
                    }
                });
                data.node = JSON.stringify(node_ids);

                // 获取服务器数据
                var server_ids = [];
                var checkedData = tree.getChecked('serverDataId');
                $.each(checkedData, function (i, v) {
                    server_ids.push(v.id);
                    if (v.children !== undefined && v.children.length > 0) {
                        $.each(v.children, function (ii, vv) {
                            server_ids.push(vv.id);
                        });
                    }
                });
                data.server = JSON.stringify(server_ids);
                return data;
            });

        }
    };
    return Controller;
});