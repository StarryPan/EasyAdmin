define(["jquery", "easy-admin"], function ($, ea) {

    // Layui工具类
    var util = layui.util;

    var Controller = {
        index: function () {

            // 获取管理员列表
            let admin_list   =  eval('(' + $('#admin_list').val() + ')');

            // 标签表格KEY
            let tabTable_key = 'systemlog';

            // 标签切换控制方法
            let tabChangeFun = function(tab_key) {

                // Table初始化参数
                let init = {
                    table_elem: '#currentTable_' + tab_key,
                    table_render_id: 'currentTableRenderId_' + tab_key,
                    index_url: 'system.log/' + tab_key,
                };

                // 初始化表头
                let init_cols = [];
                init_cols.push({field: 'id', width: 80, title: 'ID', search: false});
                init_cols.push({field: 'month', title: '日志月份', hide: true, search: 'time', timeType: 'month', searchValue: util.toDateString(new Date(), 'yyyy-MM')});
                init_cols.push({field: 'admin_id', title: '后台用户', minWidth: 80, selectList: admin_list});
                init_cols.push({field: 'method', minWidth: 80, title: '请求方法'});
                init_cols.push({field: 'url', minWidth: 80, title: '路由地址'});
                init_cols.push({field: 'content', minWidth: 80, title: '操作内容'});

                // CURL请求的字段
                if (tab_key == 'curllog') {
                    init_cols.push({field: 'api', minWidth: 80, title: '操作接口'});
                    init_cols.push({field: 'title', minWidth: 80, title: '日志标题'});
                    init_cols.push({field: 'response', minWidth: 80, title: '响应内容'});
                }
                
                // ERROR报错的字段
                if (tab_key == 'errorlog') {
                    init_cols.push({field: 'response', minWidth: 160, title: '报错内容'});
                }

                init_cols.push({field: 'ip', minWidth: 80, title: 'IP地址'});
                init_cols.push({field: 'useragent', minWidth: 80, title: 'useragent'});
                init_cols.push({field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'});

                ea.table.render({
                    init: init,
                    toolbar: ['refresh'],
                    cols: [ init_cols ],
                });

                ea.listen();
            };

            layui.use('element', function(){
                var element = layui.element;

                tabChangeFun( tabTable_key );

                //监听Tab切换，以改变地址hash值
                element.on('tab(docDemoTabBrief)', function(){
                    tabTable_key = $(this).attr('tab-table');
                    tabChangeFun( tabTable_key );
                });
            });
        },
    };

    return Controller;
});