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
                index_url: 'stat.overview/index',
                export_url: 'stat.overview/export',
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
                param += '?exchange_rate=' + $('[name="exchange_rate"]').val();// 汇率
                param += '&server_id=' + $('[name="server_id"]').val();// 服务器ID
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
                        {field: 'date', minWidth: 100, title: '日期<br/>date'},
                        {field: 'reg_num', minWidth: 100, title: '注册人数<br/>reg_num', sort: true},
                        {field: 'login_num', minWidth: 100, title: 'DAU<br/>login_num', sort: true},
                        {field: 'pay_num', minWidth: 100, title: '付费次数<br/>pay_num', sort: true},
                        {field: 'people_num', minWidth: 100, title: '付费人数<br/>people_num', sort: true},
                        {field: 'total_money', minWidth: 100, title: '付费金额<br/>total_money', sort: true},
                        {field: 'pay_rate', minWidth: 100, title: '付费率<br/>pay_rate', sort: true},
                        {field: 'pay_arpu', minWidth: 100, title: 'ARPU<br/>pay_arpu', sort: true},
                        {field: 'pay_arppu', minWidth: 200, title: 'ARPPU<br/>pay_arppu', sort: true},
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