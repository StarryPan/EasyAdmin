define(["jquery", "easy-admin"], function ($, ea) {

    // Layui工具类
    var util = layui.util;

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'server.logs/index',
                open_search: true,
            };

            // 获取服务器列表
            let server_list = eval('(' + $('#server_list').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'server_id', title: '服务器', hide: true, search: 'select', selectList: server_list, selectDef: '请选择服务器'},
                    {field: 'month', title: '查询日期', hide: true, search: 'time', timeType: 'date', searchValue: util.toDateString(new Date(), 'yyyy-MM-dd')},
                    {field: 'type', width: 200, title: '日志类型', search: 'select', selectList: {'ERR': 'Error', 'ERRAppException': 'ERRAppException'}, selectDef: '全部日志类型'},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'content', title: '日志内容<br/>content'},
                    {field: 'create_time', width: 200, title: '创建时间', search: 'range'},
                ]],
                toolbar: [ 
                    'refresh', 
                ]
            });

            ea.listen();
        },
    };
    return Controller;
});