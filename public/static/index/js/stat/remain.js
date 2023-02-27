define(["jquery", "easy-admin"], function ($, ea) {

    /**
     * 查询日志-控制类
     */
    var Controller = {

        index: function () {

            // 表头
            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'stat.remain/index',
                export_url: 'stat.remain/export',
                open_search: false,
            };
            
            // 之前的TableID
            let before_tableid = '';

            // 调用监听
            ea.listen(function(data) {
                return data;
            }, function() {

                // 强制关闭筛选框
                ea.table.switchToolbar(before_tableid, true);

                // 设置参数
                let param = '';
                param += '?server_id=' + $('[name="server_id"]').val();// 服务器ID
                param += '&repeat=' + $('[name="repeat"]').val();// ip去重
                param += '&start=' + $('[name="start"]').val();// 起始日期
                param += '&end=' + $('[name="end"]').val();// 结束日期
                init.index_url += param;
                init.export_url += param;

                // 修改TableId
                init.table_render_id += '_' + $('[name="exchange_rate"]').val();
                before_tableid        = init.table_render_id;

                // 初始化Table
                ea.table.render({
                    init: init,
                    cols: [[
                        {field: 'reg_date', minWidth: 100, title: '日期<br/>reg_date'},
                        {field: 'reg_num', minWidth: 100, title: '注册人数<br/>reg_num', sort: true},
                        {field: 'day_1', minWidth: 100, title: '次留<br/>day_1', sort: true},
                        {field: 'day_2', minWidth: 100, title: '三留<br/>day_2', sort: true},
                        {field: 'day_3', minWidth: 100, title: '四留<br/>day_3', sort: true},
                        {field: 'day_4', minWidth: 100, title: '五留<br/>day_4', sort: true},
                        {field: 'day_5', minWidth: 100, title: '六留<br/>day_5', sort: true},
                        {field: 'day_6', minWidth: 100, title: '七留<br/>day_6', sort: true},
                        {field: 'day_14', minWidth: 100, title: '十五留<br/>day_14', sort: true},
                        {field: 'day_30', minWidth: 100, title: '三十留<br/>day_30', sort: true},
                    ]],
                    toolbar: [
                        'refresh'
                    ],
                });
            });

            // 范围时间组件
            ea.api.rangeDate({type: 'date', start_elem: '[name="start"]', end_elem: '[name="end"]'});
        },
    };
    return Controller;
});