define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'activity.seedata/index',
                delete_url: 'activity.seedata/delete',
                modify_url: 'activity.seedata/modify',
                open_search: true,
            };
            
            // 获取类型列表
            let atype_list = eval('(' + $('#atype_list').val() + ')');

            // 获取开启类型
            let open_types = eval('(' + $('#open_types').val() + ')');

            // 获取服务器列表
            let server_list = eval('(' + $('#server_list').val() + ')');

            ea.table.config.switch_confirm = {title: '确认要'};
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'server_id', title: '服务器', hide: true, search: 'select', selectList: server_list, selectDef: '请选择服务器'},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'title', width: 120, title: '标题<br/>title', edit: 'text'},
                    {field: 'sort', width: 80, title: '排序<br/>sort', edit: 'text'},
                    {field: 'atype', minWidth: 80, title: '类型<br/>atype', search: 'select', selectList: atype_list},
                    {field: 'config', minWidth: 200, title: '配置<br/>config', search: false},
                    {field: 'quests', minWidth: 200, title: '任务<br/>quests', search: false},
                    {field: 'status', minWidth: 80, title: '状态<br/>status', search: 'select', selectList: {0: '关闭', 1: '正常'}, templet: ea.table.switch},
                    {field: 'open_type', minWidth: 80, title: '开启类型<br/>open_type', search: 'select', selectList: open_types},
                    {field: 'open_value', minWidth: 80, title: '开启数值<br/>open_value', search: false},
                    {field: 'update_time', minWidth: 200, title: '更新时间<br/>update_time', search: 'range'},
                    {field: 'create_time', minWidth: 200, title: '创角时间<br/>create_time', search: 'range'},
                    {
                        width: 100,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'delete'
                        ]
                    }
                ]],
                toolbar: [ 
                    'refresh', 
                    'delete', 
                ],
                modifyReload: false,
            });
            
            ea.listen();
        },
    };
    return Controller;
});