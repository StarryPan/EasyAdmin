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
                index_url: 'stat.chest/index',
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

                // 获取表名
                let cfg_id      = $('[name="cfg_id"]').val();

                // 服务器ID
                init.index_url += '?server_id=' + $('[name="server_id"]').val();

                // 抽卡配置ID
                init.index_url += '&cfg_id=' + cfg_id;

                // 抽卡次数
                init.index_url += '&count=' + $('[name="count"]').val();

                // 修改TableId
                init.table_render_id += '_' + cfg_id;
                before_tableid        = init.table_render_id;

                // 初始化Table
                ea.table.render({
                    init: init,
                    cols: [[
                        {field: 'type', minWidth: 100, title: '类型<br/>type'},
                        {field: 'rarity', minWidth: 100, title: '稀有度<br/>rarity', templet: ea.table.rarityName},
                        {field: 'name', minWidth: 100, title: '名称<br/>name', search: false},
                        {field: 'count', minWidth: 150, title: '数量<br/>count', sort: true},
                        {field: 'rate', minWidth: 80, title: '概率<br/>rate', sort: true, search: false},
                        {field: 'drops', minWidth: 80, title: '落点<br/>drops', sort: true, search: false},
                    ]],
                    toolbar: [
                        'refresh'
                    ],
                });
            });
        },
    };
    return Controller;
});