define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'config.uploadcfg/index',
                add_url: 'config.uploadcfg/add',
                edit_url: 'config.uploadcfg/edit',
                delete_url: 'config.uploadcfg/delete',
                modify_url: 'config.uploadcfg/modify',
            };

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'cfg_key', minWidth: 100, title: 'KEY<br/>cfg_key'},
                    {field: 'cfg_name', minWidth: 80, title: '名称<br/>cfg_name'},
                    {field: 'cfg_data', minWidth: 80, title: '配置数据<br/>cfg_data'},
                    {field: 'diff_data', minWidth: 80, title: '差异配置<br/>diff_data'},
                    {field: 'update_time', minWidth: 80, title: '修改时间<br/>update_time', search: 'range'},
                    {field: 'create_time', minWidth: 80, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '更新',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'delete'
                        ]
                    }
                ]],
                toolbar: [ 'refresh', 'add', 'delete' ]
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});