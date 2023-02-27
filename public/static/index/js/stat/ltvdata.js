define(["jquery", "easy-admin"], function ($, ea) {

    /**
     * 用户价值查询（LTV）-控制类
     */
    var Controller = {
        ControllerName: 'stat.ltvdata',

        index: function () {

            // 表头
            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: this.ControllerName + '/index',
                export_url: this.ControllerName + '/export',
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
                param += '&ltv_days=' + $('[name="ltv_days"]').val();// Ltv天数
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
                        {field: 'total_money', minWidth: 100, title: '付费总额<br/>total_money', sort: true},
                        {field: 'ltv_val_1', minWidth: 100, title: 'LTV1<br/>ltv_val_1', sort: true},
                        {field: 'ltv_val_3', minWidth: 100, title: 'LTV3<br/>ltv_val_3', sort: true},
                        {field: 'ltv_val_7', minWidth: 100, title: 'LTV7<br/>ltv_val_7', sort: true},
                        {field: 'ltv_val_14', minWidth: 100, title: 'LTV14<br/>ltv_val_14', sort: true},
                        {field: 'ltv_val_30', minWidth: 100, title: 'LTV30<br/>ltv_val_30', sort: true},
                        {field: 'ltv_val', minWidth: 100, title: 'LTV值<br/>ltv_val', sort: true},
                        
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