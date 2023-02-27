define(["jquery", "easy-admin"], function ($, ea) {

    // Layui工具类
    var util = layui.util;

    /**
     * 查询日志-控制类
     */
    var Controller = {

        index: function () {
            
            // 之前的TableID
            let before_tableid = '';

            // 调用监听
            ea.listen(function(data) {
                return data;
            }, function(res) {

                if (res.count == -1) {

                    ea.msg.success(res.msg, function () {

                        // 刷新当前网页
                        ea.api.closeCurrentOpen({refreshFrame: true});
                    });
                    return false;
                }

                // 表头
                let cols    = [{field: 'month', title: '查询月份', hide: true, search: 'time', timeType: 'month', searchValue: util.toDateString(new Date(), 'yyyy-MM')}];
                let data    = res.data;
                let cfgs    = data.cfgs;
                let comment = data.comment;

                for (const field in comment) {
                    if (Object.hasOwnProperty.call(comment, field)) {
                        let fname    = comment[field];
                        let cols_val = {field: field};

                        switch (field) {
                            case 'itemid':
                                // 设置搜索筛选列表
                                cols_val['width']      = 200;
                                cols_val['search']     = 'select';
                                cols_val['selectList'] = cfgs.item_list;
                            break;

                            case 'create_time':
                                cols_val['width']  = 200;
                                cols_val['search'] = 'range';
                            break;
                        
                            default:
                                if (fname.indexOf('：') > 0) {

                                    let list = {};
                                    let arr1 = fname.split('：');
                                    let arr2 = arr1[1].split('，');

                                    arr2.forEach(val => {
                                        let arr3 = val.split('.');
                                        list[arr3[0]] = arr3[0] + '.' + arr3[1];
                                    });
                                    
                                    // 修改标题内容
                                    fname = arr1[0];

                                    // 设置搜索筛选列表
                                    cols_val['width']      = 200;
                                    cols_val['search']     = 'select';
                                    cols_val['selectList'] = list;
                                }
                            break;
                        }

                        // 设置标题
                        cols_val['title'] = (fname || field) + '<br/>' + field;
                        cols.push(cols_val);
                    }
                }

                let init = {
                    table_elem: '#currentTable',
                    table_render_id: 'currentTableRenderId',
                    index_url: 'user.logquery/index',
                    open_search: false,
                };

                // 强制关闭筛选框
                ea.table.switchToolbar(before_tableid, true);

                // 获取表名
                let table_name  = $('[name="table_name"]').val();

                // 服务器ID
                init.index_url += '?server_id=' + $('[name="server_id"]').val();

                // 查询表名
                init.index_url += '&table_name=' + table_name;

                // 修改TableId
                init.table_render_id += '_' + table_name;
                before_tableid        = init.table_render_id;

                // 初始化Table
                ea.table.render({
                    init: init,
                    cols: [cols],
                    toolbar: [ 
                        'refresh'
                    ],
                });
            });
        },
    };
    return Controller;
});